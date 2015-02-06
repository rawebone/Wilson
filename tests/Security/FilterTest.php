<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Wilson\Tests\Security;

use Wilson\Security\Filter;

/**
 * A number of the vectors for the tests come from the PHP Source Tests:
 *
 * @see https://github.com/php/php-src/blob/master/ext/filter/tests/013.phpt
 * @see https://github.com/php/php-src/blob/master/ext/filter/tests/021.phpt
 */
class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filter
     */
    protected $filter;

    protected function setUp()
    {
        $this->filter = new Filter();
    }

    function testInt()
    {
        $this->assertEquals(123, $this->filter->int(" 123  "));
        $this->assertNull($this->filter->int("qwertyu123456dfghj"));

        $this->assertNull($this->filter->int(123, 150));
        $this->assertNull($this->filter->int(123, 100, 115));
        $this->assertEquals(123, $this->filter->int(123, 100, 130));
    }

    function testFloat()
    {
        $this->assertEquals(1.01, $this->filter->float(" 1.01 "));
        $this->assertNull($this->filter->float("haouh182701.010aa"));
    }

    function testEmail()
    {
        $this->assertEquals("a@b.c", $this->filter->email("a@b.c"));
        $this->assertNull($this->filter->email(" a@b. c"));
    }

    function testUrl()
    {
        $this->assertEquals("http://example.com", $this->filter->url("http://example.com"));
        $this->assertNull($this->filter->url("http://..com"));
    }

    function testString()
    {
        $this->assertEquals("blah", $this->filter->string("blah"));

        // @todo expand use cases
    }
}
