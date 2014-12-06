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
		$this->assertEquals(false, $ut->match("#^/url/([345])/junk$#", "/url/123/junk"));
		$this->assertEquals(true, $ut->match("#^/url/([123]+)/junk$#", "/url/123/junk"));
	}

	public function testParametersNoMatches()
	{
		$ut = new UrlTools();
		$this->assertEquals(array(), $ut->parameters("#^/url/([345])/junk$#", "/url/123/junk"));
	}

	public function testParametersMatches()
	{
		$ut = new UrlTools();

		$regex = "#^/not-a-match/(?<id>\\d+)/(?<name>[^/]+)/junk$#";
		$url = "/not-a-match/123/barry/junk";

		$params = array(
			"id" => "123",
			"name" => "barry"
		);

		$this->assertEquals($params, $ut->parameters($regex, $url));
	}
}