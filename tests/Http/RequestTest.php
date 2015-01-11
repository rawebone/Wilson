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

class RequestTest extends \PHPUnit_Framework_TestCase
{
    function testMethod()
    {
        $req = new Request();
        $req->mock(array("REQUEST_METHOD" => "POST"));

        $this->assertEquals("POST", $req->getMethod());
    }

    function testMethodOverride()
    {
        $req = new Request();
        $req->mock(array("REQUEST_METHOD" => "POST", "HTTP_X_HTTP_METHOD_OVERRIDE" => "PUT"));

        $this->assertEquals("PUT", $req->getMethod());
        $this->assertEquals("POST", $req->getOriginalMethod());
    }

    function testXHR()
    {
        $req = new Request();
        $req->mock(array("HTTP_X_REQUESTED_WITH" => "XMLHttpRequest"));

        $this->assertTrue($req->isAjax());
    }

    function testIsSecure()
    {
        $req = new Request();
        $req->mock(array("HTTPS" => "on"));

        $this->assertTrue($req->isSecure());
    }

    function testIsSafeMethod()
    {
        $req = new Request();

        $req->mock(array("REQUEST_METHOD" => "GET"));
        $this->assertTrue($req->isSafeMethod(), "GET should be a safe method");

        $req->mock(array("REQUEST_METHOD" => "HEAD"));
        $this->assertTrue($req->isSafeMethod(), "HEAD should be a safe method");

        $req->mock(array("REQUEST_METHOD" => "POST"));
        $this->assertFalse($req->isSafeMethod(), "POST should not be a safe method");
    }

    function testUserAgent()
    {
        $req = new Request();
        $req->mock(array("HTTP_USER_AGENT" => "Blah"));

        $this->assertEquals("Blah", $req->getUserAgent());
        $this->assertTrue($req->isUserAgentLike("^Blah$"));
    }

    function testGetFiles()
    {
        $files = array("A", "B", "C");

        $req = new Request();
        $req->mock(array(), array(), array(), array(), $files);

        $this->assertEquals($files, $req->getFiles());
    }

    function testGetCookies()
    {
        $cookies = array("A", "B", "C");

        $req = new Request();
        $req->mock(array(), array(), array(), $cookies);

        $this->assertEquals($cookies, $req->getCookies());
    }

    function testGetUrl()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_HOST" => "wilson.com",
            "SERVER_NAME" => "wilson_server",
            "SERVER_PORT" => 80,
        ));

        $this->assertEquals("http://wilson.com", $req->getUrl());
    }

    function testGetUrlWithCustomPort()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_HOST" => "wilson.com",
            "SERVER_NAME" => "wilson_server",
            "SERVER_PORT" => 8001,
        ));

        $this->assertEquals("http://wilson.com:8001", $req->getUrl());
    }

    function testGetUrlWithHttps()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_HOST" => "wilson.com",
            "SERVER_NAME" => "wilson_server",
            "SERVER_PORT" => 443,
            "HTTPS" => "on"
        ));

        $this->assertEquals("https://wilson.com", $req->getUrl());
    }

    function testGetUrlWithHttpsAndCustomPort()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_HOST" => "wilson.com",
            "SERVER_NAME" => "wilson_server",
            "SERVER_PORT" => 444,
            "HTTPS" => "on"
        ));

        $this->assertEquals("https://wilson.com:444", $req->getUrl());
    }

    function testIpFromForward()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_X_FORWARDED_FOR" => "192.1.1.1"
        ));

        $this->assertEquals("192.1.1.1", $req->getIp());
    }

    function testIpFromClient()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CLIENT_IP" => "192.1.1.1"
        ));

        $this->assertEquals("192.1.1.1", $req->getIp());
    }

    function testIpFromRemote()
    {
        $req = new Request();
        $req->mock(array(
            "REMOTE_ADDR" => "192.1.1.1"
        ));

        $this->assertEquals("192.1.1.1", $req->getIp());
    }

    function testGetMediaTypeWhenExists()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json;charset=utf-8"
        ));

        $this->assertEquals("application/json", $req->getMediaType());
    }

    function testGetMediaTypeWhenNotExists()
    {
        $req = new Request();
        $this->assertNull($req->getMediaType());
    }

    function testGetMediaTypeWhenNoParamsExist()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json"
        ));

        $this->assertEquals("application/json", $req->getMediaType());
    }

    function testGetHostFromHeader()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_HOST" => "abc.123.com:80"
        ));

        $this->assertEquals("abc.123.com", $req->getHost());
    }

    function testGetHostFromServer()
    {
        $req = new Request();
        $req->mock(array(
            "SERVER_NAME" => "abc.123.com"
        ));

        $this->assertEquals("abc.123.com", $req->getHost());
    }

    function testGetHostWithPort()
    {
        $req = new Request();
        $req->mock();

        $this->assertEquals("localhost:80", $req->getHostWithPort());
    }

    function testCredentials()
    {
        $req = new Request();
        $req->mock(array(
            "PHP_AUTH_USER" => "abc",
            "PHP_AUTH_PW" => "123"
        ));

        $this->assertEquals("abc", $req->getUsername());
        $this->assertEquals("123", $req->getPassword());
    }

    function testGetContentLength()
    {
        $req = new Request();
        $req->mock(array("HTTP_CONTENT_LENGTH" => 10));
        $this->assertEquals(10, $req->getContentLength());
    }

    function testGetMediaTypeParams()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json; charset=ISO-8859-4"
        ));

        $params = $req->getMediaTypeParams();
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey("charset", $params);
        $this->assertEquals("ISO-8859-4", $params["charset"]);
    }

    function testGetMediaTypeParamsWhenNotExists()
    {
        $req = new Request();
        $params = $req->getMediaTypeParams();

        $this->assertTrue(is_array($params));
        $this->assertEquals(0, count($params));
    }

    function testGetContentCharset()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json; charset=ISO-8859-4"
        ));

        $this->assertEquals("ISO-8859-4", $req->getContentCharset());
    }

    function testGetContentCharsetWhenNotExists()
    {
        $req = new Request();
        $this->assertNull($req->getContentCharset());
    }

//    function testGetPhysicalPath()
//    {
//        $req = new Request();
//        $req->mock();
//        $this->assertEquals("/index.php", $req->getPhysicalPath());
//    }
}