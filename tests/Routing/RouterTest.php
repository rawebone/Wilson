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

use Wilson\Cache;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTestCase;

class RouterTest extends ProphecyTestCase
{
	/**
	 * @var Cache|ObjectProphecy
	 */
	protected $cache;

	/**
	 * @var Router|ObjectProphecy
	 */
	protected $router;

	/**
	 * @var UrlTools|ObjectProphecy
	 */
	protected $ut;

	function setUp()
	{
		parent::setUp();

		$this->cache = $this->prophesize("Wilson\\Cache");
		$this->ut = $this->prophesize("Wilson\\Routing\\UrlTools");
		$this->router = new Router($this->cache->reveal(), $this->ut->reveal());
	}

	function getAnnotation()
	{
		return <<<'ANNOTATION'
/**
 * @route GET /this/{is}/{a}
 * @where is [A-Z]+
 * @where a \d+
 * @through middleware1
 * @through middleware2
 */
ANNOTATION;
	}

	function testBuildHandlers()
	{
		$object   = new \stdClass();
		$handlers = array("a", "b", "c");

		$expectation = array(
			array($object, "a"),
			array($object, "b"),
			array($object, "c")
		);

		$actual = $this->router->buildHandlers($object, $handlers);

		$this->assertEquals($expectation, $actual);
	}

	function testParseAnnotations()
	{
		$route = $this->router->parseAnnotations($this->getAnnotation());

		$conditions = array(
			"is" => "[A-Z]+",
			"a"  => "\\d+"
		);

		$middleware = array(
			"middleware1",
			"middleware2"
		);

		$this->assertEquals("/this/{is}/{a}", $route->uri, "URI");
		$this->assertEquals("GET", $route->method, "Method");
		$this->assertEquals($conditions, $route->conditions, "Conditions");
		$this->assertEquals($middleware, $route->middleware, "Middleware");
	}

	function testBuildTable()
	{
		$this->ut->compile("/this/is/static", Argument::type("array"))
				 ->shouldBeCalled()
				 ->willReturn("/this/is/static");

		$this->ut->compile("/this/{is}/dynamic", Argument::type("array"))
			 	 ->shouldBeCalled()
				 ->willReturn("/this/{is}/dynamic--");

		$expectation = array(
			"static" => array(
				"/this/is/static" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test1")
				)
			),
			"dynamic" => array(
				"/this/{is}/dynamic--" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test2")
				)
			)
		);

		$this->assertEquals($expectation, $this->router->buildTable(new TestResource()));
	}

	function testGetTable()
	{
		$this->ut->compile("/this/is/static", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/is/static");

		$this->ut->compile("/this/{is}/dynamic", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/{is}/dynamic--");

		$expectation = array(
			"static" => array(
				"/this/is/static" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test1")
				)
			),
			"dynamic" => array(
				"/this/{is}/dynamic--" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test2")
				)
			)
		);

		$actual = $this->router->getTable(array(new TestResource()));
		$this->assertEquals($expectation, $actual);
	}

	function testGetTableUsesCache()
	{
		$this->cache->has("router")
					->shouldBeCalled()
					->willReturn(true);

		$this->cache->get("router")
					->shouldBeCalled()
					->willReturn("abc");


		$table = $this->router->getTable(array(new TestResource()));
		$this->assertEquals("abc", $table);
	}

	function testStaticMatch()
	{
		$this->ut->compile("/this/is/static", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/is/static");

		$this->ut->compile("/this/{is}/dynamic", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/{is}/dynamic--");

		$resource  = new TestResource();
		$resources = array("Wilson\\Tests\\Routing\\TestResource" => $resource);

		$match = $this->router->match($resources, "GET", "/this/is/static");

		$this->assertEquals(Router::FOUND, $match->status, "Match");
		$this->assertEquals(array(array($resource, "test1")), $match->handlers, "Handlers");
	}

	function testDynamicMatch()
	{
		$this->ut->compile("/this/is/static", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/is/static");

		$this->ut->compile("/this/{is}/dynamic", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/{is}/dynamic--");

		$this->ut->match("/this/{is}/dynamic--", "/this/1/dynamic--")
				 ->shouldBeCalled()
				 ->willReturn(true);

		$this->ut->parameters("/this/{is}/dynamic--", "/this/1/dynamic--")
				 ->shouldBeCalled()
				 ->willReturn(array());

		$resource  = new TestResource();
		$resources = array("Wilson\\Tests\\Routing\\TestResource" => $resource);

		$match = $this->router->match($resources, "GET", "/this/1/dynamic--");

		$this->assertEquals(Router::FOUND, $match->status, "Match");
		$this->assertEquals(array(array($resource, "test2")), $match->handlers, "Handlers");
		$this->assertEquals(array(), $match->params, "Parameters");
	}

	function testNotFoundMatch()
	{
		$match = $this->router->match(array(), "GET", "/");
		$this->assertEquals(Router::NOT_FOUND, $match->status);
	}

	function testMethodNotAllowedMatch()
	{
		$this->ut->compile("/this/is/static", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/is/static");

		$this->ut->compile("/this/{is}/dynamic", Argument::type("array"))
			->shouldBeCalled()
			->willReturn("/this/{is}/dynamic--");

		$resource  = new TestResource();
		$resources = array("Wilson\\Tests\\Routing\\TestResource" => $resource);

		$match = $this->router->match($resources, "POST", "/this/is/static");

		$this->assertEquals(Router::METHOD_NOT_ALLOWED, $match->status, "Match");
		$this->assertEquals(array("GET"), $match->allowed, "Allowed");
	}
}

class TestResource
{
	/**
	 * @route GET /this/is/static
	 */
	function test1() { }

	/**
	 * @route GET /this/{is}/dynamic
	 */
	function test2() { }

	/**
	 * This shouldn't pull through
	 */
	function test3() { }

	/**
	 * This shouldn't pull through
	 *
	 * @route GET /abc
	 */
	protected function test4() { }
}
