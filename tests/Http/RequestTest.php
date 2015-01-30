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
        $this->assertTrue($req->isUserAgentLike("/^Blah$/"));
    }

    function testGetFiles()
    {
        $files = array("A", "B", "C");

        $req = new Request();
        $req->mock(array(), array(), array(), array(), $files);

        $this->assertEquals($files, $req->getFiles());
    }

    function testCookiesBuilt()
    {
        $cookies = array("my_cookie" => "my_value");

        $req = new Request();
        $req->mock(array(), array(), array(), $cookies);

        $reqCookies = $req->getCookies();

        $this->assertNotEmpty($reqCookies);
        $this->assertInstanceOf("Wilson\\Http\\Cookie", current($reqCookies));
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

    function testGetContentMimeTypeWhenExists()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json;charset=utf-8"
        ));

        $this->assertEquals("application/json", $req->getContentMimeType());
    }

    function testGetContentMimeTypeWhenNotExists()
    {
        $req = new Request();
        $this->assertNull($req->getContentMimeType());
    }

    function testGetContentMimeTypeWhenNoParamsExist()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json"
        ));

        $this->assertEquals("application/json", $req->getContentMimeType());
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

    function testGetContentMimeTypeParameters()
    {
        $req = new Request();
        $req->mock(array(
            "HTTP_CONTENT_TYPE" => "application/json; charset=ISO-8859-4"
        ));

        $params = $req->getContentMimeTypeParameters();
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey("charset", $params);
        $this->assertEquals("ISO-8859-4", $params["charset"]);
    }

    function testGetContentMimeTypeParametersWhenNotExists()
    {
        $req = new Request();
        $params = $req->getContentMimeTypeParameters();

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

    function testGetProtocol()
    {
        $req = new Request();

        $req->mock();
        $this->assertEquals("HTTP/1.1", $req->getProtocol());

        $req->mock(array("SERVER_PROTOCOL" => "HTTP/1.0"));
        $this->assertEquals("HTTP/1.0", $req->getProtocol());
    }

    function testGetContent()
    {
        $req = new Request();
        $req->mock(array(), array(), array(), array(), array(), "Blah");

        $this->assertEquals("Blah", $req->getContent());
    }

    function testIsFormDataViaMethod()
    {
        $req = new Request();

        $req->mock();
        $this->assertFalse($req->isFormData());

        $req->mock(array("REQUEST_METHOD" => "POST"));
        $this->assertTrue($req->isFormData());

        $req->mock(array("REQUEST_METHOD" => "POST", "HTTP_X_HTTP_METHOD_OVERRIDE" => "PUT"));
        $this->assertTrue($req->isFormData());
    }

    function testIsFormDataViaContentType()
    {
        $req = new Request();
        $req->mock(array("HTTP_CONTENT_TYPE" => "application/x-www-form-urlencoded"));

        $this->assertTrue($req->isFormData());
    }

    function testGetModifiedSince()
    {
        $req = new Request();
        $req->mock(array("HTTP_IF_MODIFIED_SINCE" => "abc"));

        $this->assertEquals("abc", $req->getModifiedSince());
    }

    function testGetETags()
    {
        $req = new Request();
        $req->mock(array("HTTP_IF_NONE_MATCH" => '"xyzzy", "r2d2xxxx", "c3piozzzz"'));
        $this->assertEquals(array('"xyzzy"', '"r2d2xxxx"', '"c3piozzzz"'), $req->getETags());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in subdirectory;
     */
    function testParsesPathsWithoutUrlRewriteInSubdirectory()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/foo/index.php",
            "SCRIPT_FILENAME" => "/var/www/foo/index.php",
            "REQUEST_URI" => "/foo/index.php/bar/xyz",
            "PATH_INFO" => "/bar/xyz",
        ));

        $this->assertEquals("/bar/xyz", $req->getPathInfo());
        $this->assertEquals("/foo/index.php", $req->getPhysicalPath());
        $this->assertEquals("/foo/index.php/bar/xyz", $req->getPath());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite is disabled;
     * App installed in root directory;
     */
    function testParsesPathsWithoutUrlRewriteInRootDirectory()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/var/www/index.php",
            "REQUEST_URI" => "/index.php/bar/xyz",
            "PATH_INFO" => "/bar/xyz",
        ));

        $this->assertEquals("/bar/xyz", $req->getPathInfo());
        $this->assertEquals("/index.php", $req->getPhysicalPath());
        $this->assertEquals("/index.php/bar/xyz", $req->getPath());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite disabled;
     * App installed in root directory;
     * Requested resource is "/";
     */
    function testParsesPathsWithoutUrlRewriteInRootDirectoryForAppRootUri()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/var/www/index.php",
            "REQUEST_URI" => "/index.php",
        ));

        $this->assertEquals("/", $req->getPathInfo());
        $this->assertEquals("/index.php", $req->getPhysicalPath());
        $this->assertEquals("/index.php/", $req->getPath());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in subdirectory;
     */
    function testParsesPathsWithUrlRewriteInSubdirectory()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/foo/index.php",
            "SCRIPT_FILENAME" => "/var/www/foo/index.php",
            "REQUEST_URI" => "/foo/bar/xyz",
        ));

        $this->assertEquals("/bar/xyz", $req->getPathInfo());
        $this->assertEquals("/foo", $req->getPhysicalPath());
        $this->assertEquals("/foo/bar/xyz", $req->getPath());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     */
    public function testParsesPathsWithUrlRewriteInRootDirectory()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/var/www/index.php",
            "REQUEST_URI" => "/bar/xyz",
        ));

        $this->assertEquals("/bar/xyz", $req->getPathInfo());
        $this->assertEquals("", $req->getPhysicalPath());
        $this->assertEquals("/bar/xyz", $req->getPath());
    }

    /**
     * Test parses script name and path info
     *
     * Pre-conditions:
     * URL Rewrite enabled;
     * App installed in root directory;
     * Requested resource is "/"
     */
    public function testParsesPathsWithUrlRewriteInRootDirectoryForAppRootUri()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/var/www/index.php",
            "REQUEST_URI" => "/",
        ));

        $this->assertEquals("/", $req->getPathInfo());
        $this->assertEquals("", $req->getPhysicalPath());
        $this->assertEquals("/", $req->getPath());
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     */
    function testParsesQueryString()
    {
        $req = new Request();
        $req->mock(array(
            "QUERY_STRING" => "one=1&two=2&three=3"
        ));

        $this->assertEquals("one=1&two=2&three=3", $req->getQueryString());
    }

    /**
     * Test removes query string from PATH_INFO when using URL Rewrite
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] exists and is not empty;
     * URL Rewrite enabled;
     */
    function testRemovesQueryStringFromPathInfo()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/foo/index.php",
            "SCRIPT_FILENAME" => "/var/www/foo/index.php",
            "REQUEST_URI" => "/foo/bar/xyz?one=1&two=2&three=3",
            "QUERY_STRING" => "one=1&two=2&three=3"
        ));

        $this->assertEquals("/bar/xyz", $req->getPathInfo());
    }

    /**
     * Test environment's PATH_INFO retains URL encoded characters (e.g. #)
     *
     * In earlier version, \Slim\Environment would use PATH_INFO instead
     * of REQUEST_URI to determine the root URI and resource URI.
     * Unfortunately, the server would URL decode the PATH_INFO string
     * before it was handed to PHP. This prevented certain URL-encoded
     * characters like the octothorpe from being delivered correctly to
     * the Slim application environment. This test ensures the
     * REQUEST_URI is used instead and parsed as expected.
     */
    function testPathInfoRetainsUrlEncodedCharacters()
    {
        $req = new Request();
        $req->mock(array(
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/var/www/index.php",
            "REQUEST_URI" => "/foo/%23bar",
            "PATH_INFO" => "/bar/xyz",
        ));

        $this->assertEquals("/foo/%23bar", $req->getPathInfo());
    }

    /**
     * Test parses query string
     *
     * Pre-conditions:
     * $_SERVER['QUERY_STRING'] does not exist;
     */
    public function testParsesQueryStringThatDoesNotExist()
    {
        $req = new Request();
        $req->mock();

        $this->assertEquals("", $req->getQueryString());
    }
}