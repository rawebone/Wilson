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

use Wilson\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
	function testRoutesFor()
	{
		$ar = new Router(array(new Resource()));


	}
}

class Resource
{
	/**
	 * @route GET /blah/{id}
	 */
	function method()
	{

	}
}