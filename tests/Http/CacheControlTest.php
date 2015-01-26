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

use Wilson\Http\CacheControl;
use Wilson\Http\Response;

class CacheControlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheControl
     */
    protected $cacheControl;

    /**
     * @var Response
     */
    protected $response;

    protected function setUp()
    {
        $this->response = new Response();
        $this->cacheControl = new CacheControl($this->response);
    }

    function testNoCache()
    {
        $this->cacheControl->doNotCache();
        $this->cacheControl->makeCacheHeaders();

        $this->assertEquals("no-cache", $this->response->getHeader("Cache-Control"));
        $this->assertEquals("no-cache", $this->response->getHeader("Pragma"));
        $this->assertEquals(-1, $this->response->getHeader("Expires"));
    }

    function testMaxAge()
    {
        $this->cacheControl->age(100, 100);
        $this->cacheControl->makeCacheHeaders();

        $this->assertEquals(
            "max-age=100, s-maxage=100",
            $this->response->getHeader("Cache-Control")
        );

        $this->assertNotNull($this->response->getHeader("Expires"));
    }

    function testPublicPrivate()
    {
        $this->cacheControl->makePublic();
        $this->cacheControl->makeCacheHeaders();

        $this->assertEquals("public", $this->response->getHeader("Cache-Control"));

        $this->cacheControl->makePrivate();
        $this->cacheControl->makeCacheHeaders();

        $this->assertEquals("private", $this->response->getHeader("Cache-Control"));
    }

    function testGeneral()
    {
        $this->cacheControl->doNotStore()
                           ->doNotTransform()
                           ->makePublic()
                           ->revalidate(true, true);
        $this->cacheControl->makeCacheHeaders();

        $this->assertEquals(
            "no-store, no-transform, public, must-revalidate, proxy-revalidate",
            $this->response->getHeader("Cache-Control")
        );
    }

    function testNoCacheControlWhenEmpty()
    {
        $this->cacheControl->makeCacheHeaders();
        $this->assertNull($this->response->getHeader("Cache-Control"));
    }
}
