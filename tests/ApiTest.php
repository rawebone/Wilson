<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests;

use Exception;
use Prophecy\PhpUnit\ProphecyTestCase;
use Wilson\Api;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Services;

class ApiTest extends ProphecyTestCase
{
    function testDefaultPrepare()
    {
        $api  = new Api();
        $req  = new Request();
        $resp = new Response();

        $api->defaultPrepare($req, $resp);
    }

    function testDefaultError()
    {
        $api  = new Api();
        $req  = new Request();
        $resp = new Response();
        $ex   = new Exception("Blah");
        $srvc = new Services();

        $api->defaultError($req, $resp, $srvc, $ex);

        $this->assertEquals(500, $resp->getStatus());
        $this->assertEquals("text/html", $resp->getHeader("Content-Type"));
        $this->assertEquals("<pre>$ex</pre>", $resp->getBody());
    }

    function testDefaultNotFound()
    {
        $api  = new Api();
        $req  = new Request();
        $resp = new Response();
        $srvc = new Services();

        $api->defaultNotFound($req, $resp, $srvc);

        $this->assertEquals(404, $resp->getStatus());
        $this->assertEquals("text/html", $resp->getHeader("Content-Type"));
        $this->assertEquals("<b>Not Found</b>", $resp->getBody());
    }

    function testCreateCache()
    {
        $api = new Api();
        $api->cacheFile = __FILE__ . ".cache";
        $api->createCache();

        $this->assertEquals(true, is_file($api->cacheFile));
        unlink($api->cacheFile);
    }

    function testMethodNotAllowedViaPost()
    {
        $api = new Api();
        $api->testing = true;
        $api->resources = array(__NAMESPACE__ . "\\ResourceFixture");

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/route-1", "REQUEST_METHOD" => "POST"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals(405, $resp->getStatus());
        $this->assertEquals("GET", $resp->getHeader("Allow"));
    }

    function testNotAllowedViaOptions()
    {
        $api = new Api();
        $api->testing = true;
        $api->resources = array(__NAMESPACE__ . "\\ResourceFixture");

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/route-1", "REQUEST_METHOD" => "OPTIONS"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals(200, $resp->getStatus());
        $this->assertEquals("GET", $resp->getHeader("Allow"));
    }

    function testNotFound()
    {
        $api = new Api();
        $api->testing = true;

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals(404, $resp->getStatus());
        $this->assertEquals("<b>Not Found</b>", $resp->getBody());
    }

    function testFoundMiddlewareAbort()
    {
        $api = new Api();
        $api->testing = true;
        $api->resources = array(__NAMESPACE__ . "\\ResourceFixture");

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/route-2"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals("failed", $resp->getBody());
    }

    function testFound()
    {
        $api = new Api();
        $api->testing = true;
        $api->resources = array(__NAMESPACE__ . "\\ResourceFixture");

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/route-1"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals("found", $resp->getBody());
    }

    function testFoundButThrowsException()
    {
        $api = new Api();
        $api->testing = true;
        $api->resources = array(__NAMESPACE__ . "\\ResourceFixture");

        $req = new Request();
        $req->mock(array("REQUEST_URI" => "/route-3"));

        $resp = new Response();

        $api->dispatch($req, $resp);

        $this->assertEquals(500, $resp->getStatus());
    }

    function testSendsResponse()
    {
        $api = new Api();
        $req = new Request();
        $req->mock();

        /** @var Response|\Prophecy\Prophecy\ObjectProphecy $resp */
        $resp = $this->prophesize("Wilson\\Http\\Response");
        $resp->setStatus(404)->shouldBeCalled();
        $resp->setHeader("Content-Type", "text/html")->shouldBeCalled();
        $resp->setBody("<b>Not Found</b>")->shouldBeCalled();
        $resp->prepare($req)->shouldBeCalled();
        $resp->send()->shouldBeCalled();

        $api->dispatch($req, $resp->reveal());
    }

    function testApiMakesRequestAndResponse()
    {
        $_SERVER = array_merge($_SERVER, array(
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/",
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "",
            "QUERY_STRING" => "",
            "SERVER_NAME" => "localhost",
            "SERVER_PORT" => 80,
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "ACCEPT" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "ACCEPT_LANGUAGE" => "en-US,en;q=0.8",
            "ACCEPT_CHARSET" => "ISO-8859-1,utf-8;q=0.7,*;q=0.3",
            "USER_AGENT" => "Wilson Framework",
            "REMOTE_ADDR" => "127.0.0.1",
            "HTTPS" => "off"
        ));

        $api = new Api();
        $api->testing = true;
        $api->dispatch();

        $this->assertInstanceOf("Wilson\\Http\\Request", $api->lastRequest);
        $this->assertInstanceOf("Wilson\\Http\\Response", $api->lastResponse);
    }
}

class ResourceFixture
{
    /**
     * @route GET /route-1
     */
    function route1(Request $request, Response $response)
    {
        $response->setBody("found");
    }

    /**
     * @route GET /route-2
     * @through fail
     */
    function route2(Request $request, Response $response)
    {
        $response->setBody("Oops, this isn't right");
    }

    /**
     * @route GET /route-3
     */
    function route3()
    {
        throw new \ErrorException("blah");
    }

    function fail(Request $request, Response $response)
    {
        $response->setBody("failed");
        return false;
    }
}