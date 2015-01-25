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
use Wilson\Http\Sender;
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

    function testPrepareWithNotModified()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock(array(
            "HTTP_IF_MODIFIED_SINCE" => "Sun, 25 Aug 2013 18:33:31 GMT",
        ));

        $response = new Response();
        $response->setStatus(204);
        $response->setHeader("Content-Type", "blah");
        $response->setHeader("Content-Length", 1);
        $response->setDateHeader("Last-Modified", new \DateTime("Sun, 25 Aug 2013 18:32:31 GMT"));
        $response->setBody("hello");

        $proxy->prepare($request, $response);

        $this->assertEquals(304, $response->getStatus());
        $this->assertEmpty($response->getBody());
        $this->assertNull($response->getHeader("Content-Type"));
        $this->assertNull($response->getHeader("Content-Length"));
    }

    function testSend()
    {
        HeaderStack::reset();

        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->html("yes");

        $sender = new Sender();

        ob_start();
        $sender->send($request, $response);

        $this->assertEquals("yes", ob_get_clean());
        $this->assertEquals(array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/html",
            "Date: " . $response->getHeader("Date"),
            "Content-Length: 3"
        ), HeaderStack::stack());
    }

    function testCacheMiss()
    {
        $proxy = SenderProxy::sender();
        $request = new Request();
        $request->mock();

        $response = new Response();
        $response->whenCacheMissed(function () use ($response)
        {
            $response->json(array(1, 2, 3));
        });

        $proxy->prepare($request, $response);

        $this->assertEquals(200, $response->getStatus());
        $this->assertSame("[1,2,3]", $response->getBody());
        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
        $this->assertEquals(7, $response->getHeader("Content-Length"));
    }
}
