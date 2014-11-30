<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests;

use Wilson\Environment;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
	function testInstantiate()
	{
		$env = new Environment();
		$this->assertEquals("development", $env->environment);
		$this->assertEmpty($env->cachePath);
	}

	function testInstantiateWithOverrides()
	{
		$env = new Environment(array("environment" => "production"));
		$this->assertEquals("production", $env->environment);
	}

	function testIsProduction()
	{
		$env = new Environment(array("environment" => "production"));
		$this->assertEquals(true, $env->production());
	}

	/**
	 * @expectedException \ErrorException
	 */
	function testSetThrowsException()
	{
		$env = new Environment();
		$env->environment = "production";
	}
}
 