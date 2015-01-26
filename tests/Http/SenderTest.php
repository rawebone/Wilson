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

class SenderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Sender
     */
    protected $sender;

    protected function setUp()
    {
        HeaderStack::reset();

        $this->request = new Request();
        $this->response = new Response();
        $this->sender = new Sender($this->request, $this->response);
    }

    protected function send()
    {
        ob_start();
        $this->sender->send();
        return ob_get_clean();
    }

    function testNormalResponse()
    {
        $this->request->mock();

        $response = $this->response;
        $response->html("Hello");

        // Cache is missed by default
        $response->whenCacheMissed(function () use ($response)
        {
            $response->setHeader("X-Cache-Miss", 1);
        });

        $this->assertEquals("Hello", $this->send());

        $expected = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/html",
            "Date: " . $this->response->getHeader("Date"),
            "X-Cache-Miss: 1",
            "Content-Length: 5"
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testStreamResponse()
    {
        $this->request->mock();

        $this->response->html(function () { echo "Hello"; });

        $this->assertEquals("Hello", $this->send());

        $expected = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/html",
            "Date: " . $this->response->getHeader("Date"),
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testCustomStatusResponse()
    {
        $this->request->mock();
        $this->response->setStatus(320);

        $this->assertEquals("", $this->send());

        $expected = array(
            "HTTP/1.1 320",
            "Date: " . $this->response->getHeader("Date"),
            "Content-Length: 0"
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testCacheControlRespected()
    {
        $this->request->mock();
        $this->response->html("Hello");
        $this->response->getCacheControl()
                       ->makePublic();

        $this->assertEquals("Hello", $this->send());

        $expected = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/html",
            "Date: " . $this->response->getHeader("Date"),
            "Cache-Control: public",
            "Content-Length: 5"
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testNotModifiedIsCalled()
    {
        $this->request->mock(array("HTTP_IF_NONE_MATCH" => "*"));
        $this->response->setETag("abc123");
        $this->response->setBody("abc");
        $this->response->setHeader("Content-Length", 3);
        $this->response->whenCacheMissed(function () { throw new \Exception(); });

        $this->assertEmpty("", $this->send());

        $expected = array(
            "HTTP/1.1 304 Not Modified",
            "ETag: \"abc123\"",
            "Date: " . $this->response->getHeader("Date"),
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testBodyNotSentWhenHeadRequest()
    {
        $this->request->mock(array("REQUEST_METHOD" => "HEAD"));
        $this->response->setBody("abc");
        $this->response->setHeader("Content-Length", 3);

        $this->assertEmpty("", $this->send());

        $expected = array(
            "HTTP/1.1 200 OK",
            "Date: " . $this->response->getHeader("Date"),
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }

    function testProtocolsMatch()
    {
        $this->request->mock(array("SERVER_PROTOCOL" => "HTTP/1.0"));
        $this->send();

        $expected = array(
            "HTTP/1.0 200 OK",
            "Date: " . $this->response->getHeader("Date"),
            "Content-Length: 0"
        );

        $this->assertEquals($expected, HeaderStack::stack());
    }
}
