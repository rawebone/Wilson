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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Wilson\Http\Response;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Utils\Cache;

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

		$this->cache = $this->prophesize("Wilson\\Utils\\Cache");
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

		$actual = $this->router->buildHandlers("\\stdClass", $handlers);

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
		$router = new Router($this->cache->reveal(), new UrlTools());
		$expectation = TestResource::expectedTable();

		$this->assertEquals($expectation, $router->buildRoutingTableEntryForResource(new TestResource()));
	}

	function testGetTable()
	{
		$router = new Router($this->cache->reveal(), new UrlTools());
		$expectation = TestResource::expectedTable();

		$actual = $router->getRoutingTable(array(__NAMESPACE__ . "\\TestResource"));
		$this->assertEquals($expectation, $actual);
	}

	function testGetTableUsesCache()
	{
		$this->cache->get("router")
					->shouldBeCalled()
					->willReturn("abc");

		$table = $this->router->getRoutingTable(array(new TestResource()));
		$this->assertEquals("abc", $table);
	}

	function testStaticMatch()
	{
		$this->cache->get("router")
					->shouldBeCalled()
					->willReturn(TestResource::expectedTable());

		$router = new Router($this->cache->reveal(), new UrlTools());

		$resource  = new TestResource();
		$resources = array(__NAMESPACE__ . "\\TestResource");

		$match = $router->match($resources, "GET", "/this/is/static");

		$this->assertEquals(Router::FOUND, $match->status, "Match");
		$this->assertEquals(array(array($resource, "test1")), $match->handlers, "Handlers");
		$this->assertEquals(array(), $match->params, "Parameters");
	}

	function testDynamicMatch()
	{
		$resource  = new TestResource();
		$resources = array(__NAMESPACE__ . "\\TestResource");

		$this->cache->get("router")->shouldBeCalled()->willReturn(TestResource::expectedTable());
		$router = new Router($this->cache->reveal(), new UrlTools());

		$match = $router->match($resources, "GET", "/this/1/dynamic");

		$this->assertEquals(Router::FOUND, $match->status, "Match");
		$this->assertEquals(array(array($resource, "test2")), $match->handlers, "Handlers");
		$this->assertEquals(array("is" => 1), $match->params, "Parameters");
	}

	function testNotFoundMatch()
	{
		$match = $this->router->match(array(), "GET", "/");
		$this->assertEquals(Router::NOT_FOUND, $match->status);
	}

	function testMethodNotAllowedMatch()
	{
		$this->cache->get("router")->shouldBeCalled()->willReturn(TestResource::expectedTable());
		$router = new Router($this->cache->reveal(), new UrlTools());

		$resources = array(__NAMESPACE__ . "\\TestResource");

		$match = $router->match($resources, "POST", "/this/is/static");

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

	/**
	 * @route POST /this/{is}/dynamic
	 */
	function test5() { }

	static function expectedTable()
	{
		return array(
			"static" => array(
				"/this/is/static" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test1")
				)
			),
			"dynamic" => array(
				"#^/this/(?<is>[^/]+)/dynamic$#" => array(
					"_name" => "Wilson\\Tests\\Routing\\TestResource",
					"GET"   => array("test2"),
					"POST"  => array("test5")
				)
			)
		);
	}
}
