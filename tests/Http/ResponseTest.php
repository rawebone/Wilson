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
}