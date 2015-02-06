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
use Wilson\Http\HeaderStack;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Http\Sender;
use Wilson\Routing\Dispatcher;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Security\Filter;
use Wilson\Security\RequestValidation;
use Wilson\Services;
use Wilson\Tests\Fixtures\DispatcherProxy;
use Prophecy\PhpUnit\ProphecyTestCase;
use Wilson\Utils\Cache;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    protected function setUp()
    {
        HeaderStack::reset();

        $this->api = $api = new Api();
        $this->request = $req = new Request();
        $this->response = $resp = new Response();

        $router = new Router(new Cache(""), new UrlTools());
        $sender = new Sender($req, $resp);

        $validation = new RequestValidation(new Filter(), $req);

        $this->dispatcher = new Dispatcher($api, $req, $resp, $router, $sender, $validation);
        $this->api->resources = array("Wilson\\Tests\\Fixtures\\ResourceFixture");
    }

    protected function dispatch()
    {
        ob_start();
        $this->dispatcher->dispatch();
        return ob_get_clean();
    }

    function testNormalDispatch()
    {
        $this->api->prepare = function (Request $request) { $request->setParam("prepare", true); };
        $this->request->mock(array("REQUEST_URI" => "/route-1"));

        $this->assertEquals("found", $this->dispatch());
        $this->assertTrue($this->request->getParam("prepare"));
    }

    function testNormalWithMiddlewareDispatch()
    {
        $this->request->mock(array("REQUEST_URI" => "/route-4"));

        $this->assertEquals("found", $this->dispatch());
        $this->assertTrue($this->request->getParam("middleware_called"));
    }

    function testNormalWithMiddlewareAbortDispatch()
    {
        $this->request->mock(array("REQUEST_URI" => "/route-2"));

        $this->assertEquals("failed", $this->dispatch());
    }

    function testNormalDispatchWithTestingFlag()
    {
        $this->api->testing = true;
        $this->request->mock(array("REQUEST_URI" => "/route-1"));

        $this->assertEmpty($this->dispatch());
        $this->assertEmpty(HeaderStack::stack());
    }

    function testDispatchAppliesSecurity()
    {
        $this->api->testing = true;
        $this->request->mock(array("REQUEST_URI" => "/route-1"));

        $this->dispatch();

        $this->assertTrue($this->response->hasHeader("X-Frame-Options"));
        $this->assertTrue($this->response->hasHeader("X-Content-Type-Options"));
    }

    function testNotFoundDispatch()
    {
        $this->api->notFound = function (Request $request) { $request->setParam("not_found", true); };
        $this->request->mock(array("REQUEST_URI" => "/not-found"));
        $this->dispatch();

        $this->assertTrue($this->request->getParam("not_found"));
    }

    function testErrorDispatch()
    {
        $this->api->error = function (Request $request) { $request->setParam("error", true); };
        $this->request->mock(array("REQUEST_URI" => "/route-3"));
        $this->dispatch();

        $this->assertTrue($this->request->getParam("error"));
    }

    function testMethodNotAllowedDispatch()
    {
        $this->request->mock(array(
            "REQUEST_URI" => "/route-1",
            "REQUEST_METHOD" => "POST"
        ));

        $this->assertEmpty($this->dispatch());
        $this->assertEquals(405, $this->response->getStatus());
    }

    function testRouteToHandlersFailsWhenValidationExceptionThrown()
    {
        $this->request->mock(array(
            "REQUEST_URI" => "/route-5",
        ), array(
            "action" => "huohsou1.0an"
        ));

        $this->dispatch();
        $this->assertEquals(500, $this->response->getStatus());
    }
}
