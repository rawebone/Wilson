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

use Wilson\Http\Cookie;
use Wilson\Tests\Fixtures\Message;
use Wilson\Http\MessageAbstract;

class MessageAbstractTest extends \PHPUnit_Framework_TestCase
{
    function testGetSetHeader()
    {
        $msg = new Message();
        $msg->setHeader("Blah", "blah");
        $this->assertEquals("blah", $msg->getHeader("Blah"));

        $msg->unsetHeader("Blah");
        $this->assertEquals(null, $msg->getHeader("Blah"));
    }

    function testGetSetHeaders()
    {
        $msg = new Message();
        $msg->setHeaders($headers = array("Blah" => "blady"));
        $this->assertEquals($headers, $msg->getHeaders());
        $this->assertTrue($msg->hasHeader("Blah"));

        $msg->unsetHeaders(array("Blah"));
        $this->assertEmpty($msg->getHeaders());

        $msg->setHeader("abc", 123);
        $msg->setAllHeaders(array("def" => 456));
        $this->assertNull($msg->getHeader("abc"));
        $this->assertEquals(456, $msg->getHeader("def"));
    }

    function testGetSetBody()
    {
        $msg = new Message();
        $msg->setBody($a = "abc");
        $this->assertEquals($a, $msg->getBody());

        $msg->setBody($b = function () {});
        $this->assertEquals($b, $msg->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testInvalidSetBody()
    {
        $msg = new Message();
        $msg->setBody(1323);
    }

    function testParams()
    {
        $msg = new Message();

        $msg->setParam("Blah", "blah");
        $this->assertEquals("blah", $msg->getParam("Blah"));

        $msg->unsetParam("Blah");
        $this->assertEquals(null, $msg->getParam("Blah"));

        $msg->setParams($params = array("Blah" => "blady"));
        $this->assertEquals($params, $msg->getParams());

        $msg->setParam("abc", 123);
        $msg->setAllParams(array("def" => 456));
        $this->assertNull($msg->getParam("abc"));
        $this->assertEquals(456, $msg->getParam("def"));
        $this->assertTrue($msg->hasParam("def"));
    }

    function testSetDateHeader()
    {
        $dt = new \DateTime();
        $dt->setTimezone(new \DateTimeZone("UTC"));

        $msg = new Message();
        $msg->setDateHeader("Test", $dt);

        $this->assertEquals(
            $dt->format("D, d M Y H:i:s T"),
            $msg->getHeader("Test")
        );
    }

    function testCookies()
    {
        $msg = new Message();
        $msg->addCookie(new Cookie("name", "value"));

        $this->assertInstanceOf("Wilson\\Http\\Cookie", $msg->getCookie("name"));
        $this->assertCount(1, $msg->getCookies());
    }
}
