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
use Wilson\Tests\Fixtures\TestContainer;

class ServicesTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var TestContainer
	 */
	protected $container;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	protected function setUp()
	{
		$this->container = new TestContainer();
		$this->request = new Request();
		$this->response = new Response();
	}

	function testGetService()
	{
		$this->assertInstanceOf("stdClass", $this->container->service);
	}

	function testInitialisation()
	{
		$this->container->initialise(
			$this->request,
			$this->response
		);

		$this->assertTrue($this->container->valid);
	}

	function testSingletons()
	{
		$this->container->initialise(
			$this->request,
			$this->response
		);

		$this->assertEquals(1, $this->container->static);
		$this->assertEquals(1, $this->container->static);

		$this->container->initialise(
			$this->request,
			$this->response
		);

		$this->assertEquals(2, $this->container->static);
	}

	/**
	 * @expectedException \ErrorException
	 */
	function testThrowsException()
	{
		$this->container->initialise(
			$this->request,
			$this->response
		);
		$this->container->nonExistant;
	}
}
