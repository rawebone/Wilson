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
    }
}
