<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Routing;

use Prophecy\Argument;
use SebastianBergmann\Exporter\Exception;
use Wilson\Api;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Http\Sender;
use Wilson\Routing\Dispatcher;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Services;
use Wilson\Tests\Fixtures\DispatcherProxy;
use Prophecy\PhpUnit\ProphecyTestCase;
use Wilson\Utils\Cache;

class DispatcherTest extends ProphecyTestCase
{
    function testRouteToHandlersSetsParams()
    {
        $request = new Request();
        $response = new Response();
        $services = new Services();
        $router = new Router(new Cache(""), new UrlTools());
        $sender = new Sender();
        $dispatcher = new Dispatcher($router, $sender);

        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $match = (object)array(
            "handlers" => array(),
            "params" => array("blah" => "blah")
        );

        $proxy->routeToHandlers($match, $request, $response, $services);

        $this->assertEquals("blah", $request->getParam("blah"));
    }

    function testRouteToHandlersRoutesToAll()
    {
        $request = new Request();
        $response = new Response();
        $services = new Services();
        $router = new Router(new Cache(""), new UrlTools());
        $sender = new Sender();
        $dispatcher = new Dispatcher($router, $sender);

        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $match = (object)array(
            "params" => array(),
            "handlers" => array(
                function ($request) { $request->setParam("i", 1); },
                function ($request) { $request->setParam("i", 2); }
            ),
        );

        $proxy->routeToHandlers($match, $request, $response, $services);

        $this->assertEquals(2, $request->getParam("i"));
    }

    function testRouteToHandlersAborts()
    {
        $request = new Request();
        $response = new Response();
        $services = new Services();
        $router = new Router(new Cache(""), new UrlTools());
        $sender = new Sender();
        $dispatcher = new Dispatcher($router, $sender);

        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $match = (object)array(
            "params" => array(),
            "handlers" => array(
                function ($request) { return false; },
                function ($request) { $request->setParam("i", 2); }
            ),
        );

        $proxy->routeToHandlers($match, $request, $response, $services);

        $this->assertNull($request->getParam("i"));
    }

    function testRouteRequestWhereFound()
    {
        $api = new Api();
        $request = new Request();
        $response = new Response();

        $router = $this->prophesize("Wilson\\Routing\\Router");
        $router->match(array(), Argument::any(), Argument::any())->willReturn((object)array(
            "status" => Router::FOUND,
            "handlers" => array(function ($request) { $request->setParam("i", 1); }),
            "params" => array()
        ));

        $dispatcher = new Dispatcher($router->reveal(), new Sender());
        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $proxy->routeRequest($api, $request, $response);

        $this->assertEquals(1, $request->getParam("i"));
    }

    function testRouteRequestWhereNotFound()
    {
        $api = new Api();
        $api->notFound = function ($request) { $request->setParam("i", 1); };

        $request = new Request();
        $response = new Response();

        $router = $this->prophesize("Wilson\\Routing\\Router");
        $router->match(array(), Argument::any(), Argument::any())->willReturn((object)array(
            "status" => Router::NOT_FOUND
        ));

        $dispatcher = new Dispatcher($router->reveal(), new Sender());
        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $proxy->routeRequest($api, $request, $response);
        $this->assertEquals(1, $request->getParam("i"));
    }

    function testRouteRequestWhereNotAllowedWithOptions()
    {
        $api = new Api();
        $request = new Request();
        $request->mock(array("REQUEST_METHOD" => "OPTIONS"));
        $response = new Response();

        $router = $this->prophesize("Wilson\\Routing\\Router");
        $router->match(array(), Argument::type("string"), Argument::type("string"))->willReturn((object)array(
            "status" => Router::METHOD_NOT_ALLOWED,
            "allowed" => array("GET", "POST")
        ));

        $dispatcher = new Dispatcher($router->reveal(), new Sender());
        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $proxy->routeRequest($api, $request, $response);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals("GET, POST", $response->getHeader("Allow"));
    }

    function testRouteRequestWhereNotAllowedWithGet()
    {
        $api = new Api();
        $request = new Request();
        $request->mock();
        $response = new Response();

        $router = $this->prophesize("Wilson\\Routing\\Router");
        $router->match(array(), Argument::type("string"), Argument::type("string"))->willReturn((object)array(
            "status" => Router::METHOD_NOT_ALLOWED,
            "allowed" => array("GET", "POST")
        ));

        $dispatcher = new Dispatcher($router->reveal(), new Sender());
        $proxy = DispatcherProxy::dispatcher($dispatcher);

        $proxy->routeRequest($api, $request, $response);

        $this->assertEquals(405, $response->getStatus());
        $this->assertEquals("GET, POST", $response->getHeader("Allow"));
    }

    function testDispatchCallsPrepareAndErrorHandlers()
    {
        $api = new Api();
        $api->testing = true;
        $api->error = function (Request $req, Response $resp, Services $s, \Exception $e)
        {
            $req->setParam("exception_called", true);
        };

        $api->prepare = function (Request $req, Response $resp)
        {
            throw new Exception("Test");
        };

        $request = new Request();
        $response = new Response();

        $sender = $this->prophesize("Wilson\\Http\\Sender");
        $sender->send($request, $response)->shouldNotBeCalled();

        $this->getDispatcher(null, $sender->reveal())
             ->dispatch($api, $request, $response);

        $this->assertTrue($request->getParam("exception_called"));
    }

    function testDispatchCallsSender()
    {
        $api = new Api();
        $request = new Request();
        $response = new Response();

        $sender = $this->prophesize("Wilson\\Http\\Sender");
        $sender->send($request, $response)->shouldBeCalled();

        $this->getDispatcher(null, $sender->reveal())
             ->dispatch($api, $request, $response);
    }

    protected function getDispatcher(Router $router = null, Sender $sender = null)
    {
        if (!$router) {
            $router = new Router(new Cache(""), new UrlTools());
        }

        if (!$sender) {
            $sender = new Sender();
        }

        return new Dispatcher($router, $sender);
    }
}
