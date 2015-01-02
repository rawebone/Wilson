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
        $this->assertEquals(true, $resp->isInformational());

        $resp->setStatus(200);
        $this->assertEquals(true, $resp->isOk());
        $this->assertEquals(true, $resp->isSuccess());

        $resp->setStatus(300);
        $this->assertEquals(true, $resp->isRedirection());

        $resp->setStatus(400);
        $this->assertEquals(true, $resp->isClientError());

        $resp->setStatus(500);
        $this->assertEquals(true, $resp->isServerError());
    }
}