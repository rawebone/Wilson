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
use Wilson\Caching\CacheInterface;

class Router
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var UrlTools
     */
    protected $urlTools;

	/**
	 * @param CacheInterface $cache
	 * @param UrlTools $urlTools
	 */
    public function __construct(CacheInterface $cache, UrlTools $urlTools)
    {
        $this->cache = $cache;
        $this->urlTools = $urlTools;
    }

	/**
	 * @param array $resources
	 * @param string $method
	 * @param string $uri
	 * @return Route
	 */
    public function match(array $resources, $method, $uri)
    {
		$route = new Route;
		$route->status = Route::NOT_FOUND;

        foreach ($resources as $name => $resource) {
            $table = $this->buildTable($name, $resource);

            foreach ($table as $expr => $handlers) {
                if ($this->urlTools->match($expr, $uri)) {

                    if (isset($handlers[$method])) {
						$route->status   = Route::FOUND;
						$route->handlers = $this->buildHandlers($resource, $handlers[$method]);
						$route->params   = $this->urlTools->parameters($expr, $uri);

                    } else {
						$route->status  = Route::METHOD_NOT_ALLOWED;
						$route->allowed = array_keys($handlers);
                    }
                }
            }
        }

        return $route;
    }

	/**
	 * @param $resourceName
	 * @param $resource
	 * @return array
	 */
    protected function buildTable($resourceName, $resource)
    {
        $key = "router_" . $resourceName;

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $table = array();
        $reflection = new ReflectionClass($resource);

        $globalMiddleware = $this->routeMiddleware($reflection->getDocComment());

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            /** @var ReflectionMethod $method */
            $comment = $method->getDocComment();

            if (strpos($comment, "@route") === false) {
                continue;
            }

            list($httpMethod, $uri) = $this->routeAnnotation($comment);
            $conditions = $this->routeConditions($comment);
            $middleware = $this->routeMiddleware($comment);

            $compiled = $this->urlTools->compile($uri, $conditions);

            if (!isset($table[$compiled])) {
                $table[$compiled] = array();
            }

            $handlers = array();
            foreach ($globalMiddleware as $ware) {
                $handlers[] = $ware;
            }
            foreach ($middleware as $ware) {
                $handlers[] = $ware;
            }
            $handlers[] = $method->name;

            $table[$compiled][$httpMethod] = $handlers;
        }

        $this->cache->set($key, $table);
        return $table;
    }

    protected function buildHandlers($resource, array $handlers)
    {
        $return = array();
        foreach ($handlers as $handler) {
            $return[] = array($resource, $handler);
        }
        return $return;
    }

    /**
     * Returns the route associated with the annotation.
     *
     * @param string $comment
     * @return <method, uri>|null
     */
    protected function routeAnnotation($comment)
    {
        if (preg_match("/@route (GET|POST|DELETE|PUT|PATCH|OPTIONS|HEAD) ([^\r\n]+)/", $comment, $matches)) {
            return array($matches[1], $matches[2]);
        }

        return null;
    }

    /**
     * Returns the route associated with the annotation.
     *
     * @param string $comment
     * @return <name, expr>[]|null
     */
    protected function routeConditions($comment)
    {
        if (preg_match_all("/@where ([\\w]+) ([^\r\n]+)/", $comment, $matches)) {
            $conditions = array();
            for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
                $conditions[$matches[1][$i]] = $matches[2][$i];
            }

            return $conditions;
        }

        return array();
    }

    protected function routeMiddleware($comment)
    {
        if (preg_match_all("/@through ([\\w\\_]+)/", $comment, $matches)) {
            return $matches[1];
        }

        return array();
    }
}