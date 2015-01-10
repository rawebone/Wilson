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

use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Services;

class ServicesTest extends \PHPUnit_Framework_TestCase
{
	function testGetService()
	{
		$service = new TestContainer();
		$this->assertInstanceOf("stdClass", $service->service);
	}

	function testInitialisation()
	{
		$service = new TestContainer();
		$service->initialise(new Request(), new Response());

		$this->assertEquals(true, $service->valid);
	}

	function testSingletons()
	{
		$service  = new TestContainer();
		$request  = new Request();
		$response = new Response();

		$service->initialise($request, $response);

		$this->assertEquals(1, $service->static);
		$this->assertEquals(1, $service->static);

		$service->initialise($request, $response);

		$this->assertEquals(2, $service->static);
	}

	/**
	 * @expectedException \ErrorException
	 */
	function testThrowsException()
	{
		$service = new TestContainer();
		$service->initialise(new Request(), new Response());
		$service->nonExistant;
	}
}

class TestContainer extends Services
{
	protected $i = 0;

	protected function getService()
	{
		return new \stdClass();
	}

	protected function getValid()
	{
		return isset($this->request) && isset($this->response);
	}

	protected function getStatic()
	{
		return ++$this->i;
	}
}
