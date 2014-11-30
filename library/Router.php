<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Wilson;

use ReflectionClass;
use ReflectionMethod;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

class Router
{
	/**
	 * @var Dispatcher
	 */
	protected $router;

	public function __construct(array $resources, $cacheFile = null)
	{
		$self    = $this;
		$options = array("cacheFile" => $cacheFile, "cacheEnabled" => !is_null($cacheFile));
		$builder = function (RouteCollector $collector) use ($self, $resources)
		{
			foreach ($resources as $resource) {
				$self->addRoutesFor($resource, $collector);
			}
		};

		$this->router = \FastRoute\cachedDispatcher($builder, $options);
	}

	/**
	 * @param string $method
	 * @param string $uri
	 * @return array
	 */
	public function dispatch($method, $uri)
	{
		return $this->router->dispatch($method, $uri);
	}

	/**
	 * @param object $resource
	 * @param RouteCollector $collector
	 */
	protected function addRoutesFor($resource, RouteCollector $collector)
	{
		$reflection = new ReflectionClass($resource);

		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			/** @var ReflectionMethod $method */
			$comment = $method->getDocComment();

			if (strpos($comment, "@route") === false) {
				continue;
			}

			list($httpMethod, $uri) = $this->routeAnnotation($comment);

			$collector->addRoute($httpMethod, $uri, array($resource, $method->name));
		}
	}

	/**
	 * Returns the route associated with the annotation.
	 *
	 * @param string $comment
	 * @return <method, uri>|null
	 */
	protected function routeAnnotation($comment)
	{
		if (preg_match("/@route (GET|POST|DELETE|PUT|PATCH|OPTIONS|HEAD) (.*)/", $comment, $matches)) {
			return array($matches[1], $matches[2]);
		}

		return null;
	}
}