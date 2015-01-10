<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Routing;

use Wilson\Routing\UrlTools;

class UrlToolsTest extends \PHPUnit_Framework_TestCase
{
	public function testTerminate()
	{
		$ut = new UrlTools();
		$this->assertEquals("#^a$#", $ut->terminate("a"));
	}

	public function testCompileWithNoParameters()
	{
		$ut = new UrlTools();
		$this->assertEquals("#^/$#", $ut->compile("/", array()));
	}

	public function testCompileWithParameters()
	{
		$ut = new UrlTools();
		$compiled = $ut->compile("/not-a-match/{id}/{name}/junk", array("id" => "\\d+"));
		$this->assertEquals("#^/not-a-match/(?<id>\\d+)/(?<name>[^/]+)/junk$#", $compiled);
	}

	public function testMatch()
	{
		$ut = new UrlTools();
		$a  = array();
		$this->assertFalse($ut->match("#^/url/([345])/junk$#", "/url/123/junk", $a));
		$this->assertTrue($ut->match("#^/url/([123]+)/junk$#", "/url/123/junk", $a));
	}

	public function testMatchWithParameters()
	{
		$ut = new UrlTools();
		$a  = array();

		$this->assertTrue($ut->match("#^/url/(?<id>[123]+)/junk$#", "/url/123/junk", $a));
		$this->assertEquals(array("id" => "123"), $a);
	}
}