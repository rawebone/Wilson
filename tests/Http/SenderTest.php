<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Http;

use Wilson\Http\HeaderStack;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Tests\Fixtures\SenderProxy;

class SenderTest extends \PHPUnit_Framework_TestCase
{
    function testCheckProtocol()
    {
        $request = new Request();
        $request->mock(array(
            "SERVER_PROTOCOL" => "HTTP/1.0"
        ));

        $response = new Response();
        $this->assertEquals("HTTP/1.1", $response->getProtocol());

        $proxy = SenderProxy::sender();

        $proxy->checkProtocol($request, $response);
        $this->assertEquals("HTTP/1.0", $response->getProtocol());
    }

    function testCheckCacheControl()
    {
        $request = new Request();
        $request->mock(array(
            "SERVER_PROTOCOL" => "HTTP/1.0"
        ));

        $response = new Response();
        $response->setHeader("Cache-Control", "no-cache");

        $proxy = SenderProxy::sender();
        $proxy->checkCacheControl($request, $response);

        $this->assertEquals("no-cache", $response->getHeader("Pragma"));
        $this->assertEquals(-1, $response->getHeader("Expires"));
    }

    function testSendContent()
    {
        $response = new Response();
        $proxy    = SenderProxy::sender();

        $response->setBody("hello");

        ob_start();
        $proxy->sendBody($response);
        $this->assertEquals("hello", ob_get_clean());

        $response->setBody(function () { echo "hello"; });

        ob_start();
        $proxy->sendBody($response);
        $this->assertEquals("hello", ob_get_clean());
    }

    function testSendHeaders()
    {
        HeaderStack::reset();

        $proxy = SenderProxy::sender();
        $response = new Response();
        $response->setHeader("Blah", "blah");

        $proxy->sendHeaders($response);
        $this->assertEquals(array(
            "HTTP/1.1 200 OK",
            "Blah: blah"
        ), HeaderStack::stack());
    }

    function testPrepare()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->setBody("hello");

        $proxy->prepare($request, $response);
        $this->assertEquals(5, $response->getHeader("Content-Length"));
    }

    function testPrepareWithHeadRequest()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock(array("REQUEST_METHOD" => "HEAD"));

        $response = new Response();
        $response->setHeader("Content-Type", "blah");
        $response->setHeader("Content-Length", 1);
        $response->setBody("hello");

        $proxy->prepare($request, $response);

        $this->assertEmpty($response->getBody());
        $this->assertNull($response->getHeader("Content-Type"));
        $this->assertNull($response->getHeader("Content-Length"));
    }

    function testPrepareWithInformationResponse()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->setStatus(101);
        $response->setHeader("Content-Type", "blah");
        $response->setHeader("Content-Length", 1);
        $response->setBody("hello");

        $proxy->prepare($request, $response);

        $this->assertEmpty($response->getBody());
        $this->assertNull($response->getHeader("Content-Type"));
        $this->assertNull($response->getHeader("Content-Length"));
    }

    function testPrepareWithRedirectResponse()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->setStatus(304);
        $response->setHeader("Content-Type", "blah");
        $response->setHeader("Content-Length", 1);
        $response->setBody("hello");

        $proxy->prepare($request, $response);

        $this->assertEmpty($response->getBody());
        $this->assertNull($response->getHeader("Content-Type"));
        $this->assertNull($response->getHeader("Content-Length"));
    }

    function testPrepareWithNoContentResponse()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->setStatus(204);
        $response->setHeader("Content-Type", "blah");
        $response->setHeader("Content-Length", 1);
        $response->setBody("hello");

        $proxy->prepare($request, $response);

        $this->assertEmpty($response->getBody());
        $this->assertNull($response->getHeader("Content-Type"));
        $this->assertNull($response->getHeader("Content-Length"));
    }
}