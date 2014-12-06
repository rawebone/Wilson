<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Routing;

use ReflectionClass;
use ReflectionMethod;
use Wilson\Cache;

/**
 * This routing implementation is based off of nikic/fast-route, rawebone/micro
 * and symfony/routing. The router creates a table based off of all of the
 * resources which is divided by "static" and "dynamic" routes, the routes are
 * defined in public object methods which have a "@route" annotation.
 */
class Router
{
	const ROUTE_REGEX = "/@route ([A-Z]+) ([^\r\n]+)/";
	const CONDITION_REGEX = "/@where ([\\w]+) ([^\r\n]+)/";
	const THROUGH_REGEX = "/@through ([\\w\\_]+)/";

	const FOUND = 1;
	const NOT_FOUND = 2;
	const METHOD_NOT_ALLOWED = 4;

	/**
	 * @var Cache
	 */
	protected $cache;

	/**
	 * @var UrlTools
	 */
	protected $urlTools;

	/**
	 * @param Cache $cache
	 * @param UrlTools $urlTools
	 */
	public function __construct(Cache $cache, UrlTools $urlTools)
	{
		$this->cache = $cache;
		$this->urlTools = $urlTools;
	}

	/**
	 * @param array $resources
	 * @param string $method
	 * @param string $uri
	 * @return object
	 */
	public function match(array $resources, $method, $uri)
	{
		$route = new \stdClass();
		$route->status = Router::NOT_FOUND;

		$handler = null;
		$table   = $this->getTable($resources);

		if (isset($table["static"][$uri])) {
			$handler = $table["static"][$uri];

		} else {
			foreach ($table["dynamic"] as $expr => $handlers) {
				if ($this->urlTools->match($expr, $uri)) {
					$handler = $handlers;
					$route->params = $this->urlTools->parameters($expr, $uri);
					break;
				}
			}
		}

		if ($handler) {
			if (isset($handler[$method])) {
				$resource = $resources[$handler["_name"]];

				$route->status   = Router::FOUND;
				$route->handlers = $this->buildHandlers($resource, $handler[$method]);

			} else {
				$route->status  = Router::METHOD_NOT_ALLOWED;
				$route->allowed = array();

				foreach (array_keys($handler) as $method) {
					if (strpos($method, "_name") === false) {
						$route->allowed[] = $method;
					}
				}
			}
		}

		return $route;
	}

	/**
	 * @param array $resources
	 * @return array
	 */
	public function getTable(array $resources)
	{
		if ($this->cache->has("router")) {
			return $this->cache->get("router");
		}

		$table = array(
			"static"  => array(),
			"dynamic" => array()
		);

		foreach ($resources as $resource) {
			$routes = $this->buildTable($resource);

			$table["static"]  += $routes["static"];
			$table["dynamic"] += $routes["dynamic"];
		}
		return $table;
	}

	/**
	 * @param $resource
	 * @return array
	 */
	public function buildTable($resource)
	{
		$table = array(
			"static"  => array(),
			"dynamic" => array()
		);
		$reflection = new ReflectionClass($resource);

		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			/** @var ReflectionMethod $method */
			$comment = $method->getDocComment();

			if (strpos($comment, "@route") === false) {
				continue;
			}

			$notation = $this->parseAnnotations($comment);
			$compiled = $this->urlTools->compile($notation->uri, $notation->conditions);

			$type = ($compiled === $notation->uri ? "static" : "dynamic");

			if (!isset($table[$compiled])) {
				$table[$type][$compiled] = array();
				$table[$type][$compiled]["_name"] = $reflection->getName();
			}

			$notation->middleware[] = $method->name;

			$table[$type][$compiled][$notation->method] = $notation->middleware;
		}

		return $table;
	}

	/**
	 * This parses a comment for framework relevant annotations. This is kept as
	 * a single method call to maximise efficiency when caching is not available.
	 *
	 * @param string $comment
	 * @return object
	 */
	public function parseAnnotations($comment)
	{
		$annotations = array(
			"method" => "",
			"uri" => "",
			"conditions" => array(),
			"middleware" => array()
		);

		if (preg_match(Router::ROUTE_REGEX, $comment, $matches)) {
			$annotations["method"] = $matches[1];
			$annotations["uri"] = $matches[2];
		}

		if (preg_match_all(Router::CONDITION_REGEX, $comment, $matches)) {
			for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
				$annotations["conditions"][$matches[1][$i]] = $matches[2][$i];
			}
		}

		if (preg_match_all(Router::THROUGH_REGEX, $comment, $matches)) {
			$annotations["middleware"] = $matches[1];
		}

		return (object)$annotations;
	}

	/**
	 * @param object $resource
	 * @param array $handlers
	 * @return array
	 */
	public function buildHandlers($resource, array $handlers)
	{
		$return = array();
		foreach ($handlers as $handler) {
			$return[] = array($resource, $handler);
		}
		return $return;
	}
}