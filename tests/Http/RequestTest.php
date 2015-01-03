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


}