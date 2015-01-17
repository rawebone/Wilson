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
        $this->assertEquals("200 OK", Response::getMessageForCode(200));
        $this->assertNull(Response::getMessageForCode(99));
    }

    function testSetRedirect()
    {
        $url = "blah.com";

        $resp = new Response();
        $resp->setRedirect($url);

        $this->assertEquals($url, $resp->getHeader("Location"));
        $this->assertEquals(302, $resp->getStatus());

        $resp->setRedirect($url, 200);
        $this->assertEquals(200, $resp->getStatus());
    }

    function testSetExpires()
    {
        $resp = new Response();
        $resp->setExpires(null);

        $this->assertNull($resp->getHeader("Expires"));

        $now = new \DateTime();
        $resp->setExpires($now);

        $this->assertEquals(strtotime($resp->getHeader("Expires")), $now->getTimestamp());
    }

    function testSetLastModified()
    {
        $resp = new Response();
        $resp->setLastModified(null);

        $this->assertNull($resp->getHeader("Last-Modified"));

        $now = new \DateTime();
        $resp->setLastModified($now);

        $this->assertEquals(strtotime($resp->getHeader("Last-Modified")), $now->getTimestamp());
    }

    function testSetETag()
    {
        $resp = new Response();

        $resp->setHeader("ETag", "abcc");
        $resp->setETag(null);
        $this->assertNull($resp->getHeader("ETag"));

        $resp->setETag("abc");
        $this->assertEquals('"abc"', $resp->getHeader("ETag"));

        $resp->setETag("abc", true);
        $this->assertEquals('W/"abc"', $resp->getHeader("ETag"));
    }

    function testSendContentViaCallable()
    {
        $resp = new Response();
        $resp->setBody(function () { echo "Hello"; });

        ob_start();
        $resp->sendContent();
        $this->assertEquals("Hello", ob_get_clean());
    }

    function testSendContentViaString()
    {
        $resp = new Response();
        $resp->setBody("Hello");

        ob_start();
        $resp->sendContent();
        $this->assertEquals("Hello", ob_get_clean());
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

    function testCheckForModificationsWithNonSafeMethod()
    {
        $request = new Request();
        $request->mock(array(
            "REQUEST_METHOD" => "POST"
        ));

        $response = new Response();
        $this->assertFalse($response->checkForModifications($request));
    }

    function testCheckForModificationsLastModified()
    {
        $before = "Sun, 25 Aug 2013 18:32:31 GMT";
        $modified = "Sun, 25 Aug 2013 18:33:31 GMT";
        $after = "Sun, 25 Aug 2013 19:33:31 GMT";

        $request = new Request();
        $request->mock(array(
            "HTTP_IF_MODIFIED_SINCE" => $modified
        ));

        $response = new Response();
        $response->setHeader("Last-Modified", $before);

        $response->setHeader("Last-Modified", $modified);
        $this->assertTrue($response->checkForModifications($request));

        $response->setHeader("Last-Modified", $before);
        $this->assertTrue($response->checkForModifications($request));

        $response->setHeader("Last-Modified", $after);
        $this->assertFalse($response->checkForModifications($request));

        $response->setHeader("Last-Modified", "");
        $this->assertFalse($response->checkForModifications($request));
    }

    function testCheckForModificationsEtag()
    {
        $etagOne = "randomly_generated_etag";
        $etagTwo = "randomly_generated_etag_2";

        $request = new Request();
        $request->mock(array(
            "HTTP_IF_NONE_MATCH" => "$etagOne, $etagTwo, etagThree"
        ));

        $response = new Response();
        $response->setHeader("ETag", $etagOne);
        $this->assertTrue($response->checkForModifications($request));

        $response->setHeader("ETag", $etagTwo);
        $this->assertTrue($response->checkForModifications($request));

        $response->setHeader("ETag", "");
        $this->assertFalse($response->checkForModifications($request));
    }

    function testCheckForModificationsLastModifiedAndEtag()
    {
        $before = "Sun, 25 Aug 2013 18:32:31 GMT";
        $modified = "Sun, 25 Aug 2013 18:33:31 GMT";
        $after = "Sun, 25 Aug 2013 19:33:31 GMT";
        $etag = "randomly_generated_etag";

        $request = new Request();
        $request->mock(array(
            "HTTP_IF_NONE_MATCH" => "$etag, etagThree",
            "HTTP_IF_MODIFIED_SINCE" => $modified
        ));

        $response = new Response();
        $response->setHeaders(array(
           "ETag" => $etag,
            "Last-Modified" => $after
        ));
        $this->assertFalse($response->checkForModifications($request));

        $response->setHeaders(array(
            "ETag" => "non_existent_etag",
            "Last-Modified" => $before
        ));
        $this->assertFalse($response->checkForModifications($request));

        $response->setHeaders(array(
            "ETag" => $etag,
            "Last-Modified" => $modified
        ));
        $this->assertTrue($response->checkForModifications($request));
    }

    function testIsNotModifiedIfModifiedSinceAndEtagWithoutLastModified()
    {
        $modified = "Sun, 25 Aug 2013 18:33:31 GMT";
        $etag = "randomly_generated_etag";

        $request = new Request();
        $request->mock(array(
            "HTTP_IF_NONE_MATCH" => "$etag, etagThree",
            "HTTP_IF_MODIFIED_SINCE" => $modified
        ));

        $response = new Response();
        $response->setHeader("ETag", $etag);
        $this->assertTrue($response->checkForModifications($request));

        $response->setHeader("ETag", "non-existent-etag");
        $this->assertFalse($response->checkForModifications($request));
    }
}