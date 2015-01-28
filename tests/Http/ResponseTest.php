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

use Wilson\Http\Request;
use Wilson\Http\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    function testStatuses()
    {
        $resp = new Response();

        $resp->setStatus(100);
        $this->assertTrue($resp->isInformational());

        $resp->setStatus(200);
        $this->assertTrue($resp->isOk());
        $this->assertTrue($resp->isSuccess());

        $resp->setStatus(300);
        $this->assertTrue($resp->isRedirection());

        $resp->setStatus(400);
        $this->assertTrue($resp->isClientError());

        $resp->setStatus(500);
        $this->assertTrue($resp->isServerError());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetStatusBelow100()
    {
        $resp = new Response();
        $resp->setStatus(99);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetStatusAbove600()
    {
        $resp = new Response();
        $resp->setStatus(601);
    }

    function testGetMessage()
    {
        $response = new Response();

        $response->setStatus(200);
        $this->assertEquals("200 OK", $response->getMessage());

        $response->setStatus(199);
        $this->assertNull($response->getMessage());
    }

    function testSetRedirect()
    {
        $url = "blah.com";

        $resp = new Response();
        $resp->redirect($url);

        $this->assertEquals($url, $resp->getHeader("Location"));
        $this->assertEquals(302, $resp->getStatus());

        $resp->redirect($url, 200);
        $this->assertEquals(200, $resp->getStatus());
    }

    function testSetETag()
    {
        $resp = new Response();

        $resp->setETag("abc");
        $this->assertEquals('"abc"', $resp->getHeader("ETag"));

        $resp->setETag("abc", true);
        $this->assertEquals('W/"abc"', $resp->getHeader("ETag"));
    }

    function testSendContentViaCallable()
    {
        $resp = new Response();
        $resp->setBody($a = function () { echo "Hello"; });
        $this->assertSame($a, $resp->getBody());
    }

    function testSendContentViaString()
    {
        $resp = new Response();
        $resp->setBody($a = "Hello");
        $this->assertSame($a, $resp->getBody());
    }

    function testNotModified()
    {
        $response = new Response();
        $response->setHeaders(array(
            "Allow" => "",
            "Content-Encoding" => "",
            "Content-Language" => "",
            "Content-Length" => "",
            "Content-MD5" => "",
            "Content-Type" => "",
            "Last-Modified" => ""
        ));

        $response->notModified();
        $this->assertEquals(304, $response->getStatus());
        $this->assertEmpty($response->getHeaders());
    }

    function testMake()
    {
        $response = new Response();
        $response->make("abc", 302, array("blah" => 123));

        $this->assertEquals("abc", $response->getBody());
        $this->assertEquals(302, $response->getStatus());
        $this->assertEquals(123, $response->getHeader("blah"));
    }

    function testEmptyJsonResponse()
    {
        $response = new Response();
        $response->json("");

        $this->assertEquals('""', $response->getBody());
        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
        $this->assertEquals(200, $response->getStatus());
    }

    function testEmptyJsonResponseWithCustomType()
    {
        $response = new Response();
        $response->json("", 200, array("Content-Type" => "application/problem+json"));

        $this->assertEquals('""', $response->getBody());
        $this->assertEquals("application/problem+json", $response->getHeader("Content-Type"));
        $this->assertEquals(200, $response->getStatus());
    }

    function testJsonWithArray()
    {
        $response = new Response();
        $response->json(array(0, 1, 2, 3));

        $this->assertEquals("[0,1,2,3]", $response->getBody());
    }

    function testJsonWithAssocArrayCreatesJsonObject()
    {
        $response = new Response();
        $response->json(array("foo" => "bar"));

        $this->assertEquals('{"foo":"bar"}', $response->getBody());
    }

    function testJsonWithSimpleTypes()
    {
        $response = new Response();

        $response->json("foo");
        $this->assertSame('"foo"', $response->getBody());

        $response->json(0);
        $this->assertSame('0', $response->getBody());

        $response->json(0.1);
        $this->assertSame('0.1', $response->getBody());

        $response->json(true);
        $this->assertSame('true', $response->getBody());
    }

    function testGetSetProtocol()
    {
        $response = new Response();
        $this->assertEquals("HTTP/1.1", $response->getProtocol());

        $response->setProtocol("HTTP/1.0");
        $this->assertEquals("HTTP/1.0", $response->getProtocol());
    }

    function testHtml()
    {
        $response = new Response();
        $response->html("ABC");

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals("ABC", $response->getBody());
        $this->assertEquals("text/html", $response->getHeader("Content-Type"));

        $response->html("DEF", 404, array("Content-Type" => "text/xhtml"));

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals("DEF", $response->getBody());
        $this->assertEquals("text/xhtml", $response->getHeader("Content-Type"));
    }

    function testCacheMissedHandling()
    {
        $response = new Response();
        $response->whenCacheMissed(function () use ($response)
        {
            $response->json(array(1, 2, 3));
        });

        $response->cacheMissed();

        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testCacheMissedWithInvalidHandler()
    {
        $response = new Response();
        $response->whenCacheMissed(null);
    }

    function testGetCacheControl()
    {
        $response = new Response();
        $this->assertInstanceOf(
            "Wilson\\Http\\CacheControl",
            $response->getCacheControl()
        );
    }

    function testIsBodyAllowed()
    {
        $response = new Response();
        $this->assertTrue($response->isBodyAllowed());

        $response->setStatus(101);
        $this->assertFalse($response->isBodyAllowed());

        $response->setStatus(204);
        $this->assertFalse($response->isBodyAllowed());

        $response->setStatus(304);
        $this->assertFalse($response->isBodyAllowed());
    }
}
