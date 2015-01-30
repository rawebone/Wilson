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

class CookieTest extends \PHPUnit_Framework_TestCase
{
    function invalidNames()
    {
        return array(
            array(""),
            array(",MyName"),
            array(";MyName"),
            array(" MyName"),
            array("\tMyName"),
            array("\rMyName"),
            array("\nMyName"),
            array("\013MyName"),
            array("\014MyName"),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidNames
     */
    function testConstructFailsOnInvalidName($name)
    {
        new Cookie($name);
    }

    function testBasicOperation()
    {
        $cookie = new Cookie("my_cookie", "has_a_value");
        $this->assertEquals("my_cookie=has_a_value; path=/; httponly", (string)$cookie);
    }

    function testDeletedCookie()
    {
        $cookie = new Cookie("my_cookie");
        $time = gmdate("D, d-M-Y H:i:s T", time() - 31536001);
        $this->assertEquals("my_cookie=deleted; expires=$time; path=/; httponly", (string)$cookie);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGettingCookieProperty()
    {
        $cookie = new Cookie("my_cookie");
        $this->assertTrue($cookie->httpOnly);

        // This will throw an exception
        $cookie->invalid;
    }

    function testOverridingDefaults()
    {
        $dt = new \DateTime();
        $cookie = new Cookie("my_cookie", "my_value", $dt, "/blah", "//blah.com", true, false);

        $expires = $dt->setTimezone(new \DateTimeZone("UTC"))->format("D, d-M-Y H:i:s T");
        $this->assertEquals(
            "my_cookie=my_value; expires=$expires; path=/blah; domain=//blah.com; secure",
            (string)$cookie
        );
    }
}