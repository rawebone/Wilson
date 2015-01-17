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

        $msg->unsetHeaders(array("Blah"));
        $this->assertEmpty($msg->getHeaders());
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
        $req = new Message();

        $req->setParam("Blah", "blah");
        $this->assertEquals("blah", $req->getParam("Blah"));

        $req->unsetParam("Blah");
        $this->assertEquals(null, $req->getParam("Blah"));

        $req->setParams($params = array("Blah" => "blady"));
        $this->assertEquals($params, $req->getParams());
    }
}

class Message extends MessageAbstract { }