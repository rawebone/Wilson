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
use Wilson\Utils\Cache;

/**
 * This routing implementation is based off of nikic/fast-route, rawebone/micro
 * and symfony/routing. The router creates a table based off of all of the
 * resources which is divided by "static" and "dynamic" routes, the routes are
 * defined in public object methods which have a "@route" annotation.
 */
class Router
{
    /**
     * @route HTTP_METHOD URI
     */
    const ROUTE_REGEX = "/@route ([A-Z]+) ([^\r\n]+)/";

    /**
     * @where PARAMETER REGEX
     */
    const CONDITION_REGEX = "/@where ([\\w]+) ([^\r\n]+)/";

    /**
     * @through METHOD_NAME
     */
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
     * Matches a request against the resources currently available.
     * The object returned is in the format of:
     *
     * stdClass {
     *     $status;   // Router::FOUND, Router::NOT_FOUND, Router::METHOD_NOT_ALLOWED
     *     $allowed;  // An array of allowed HTTP methods (when METHOD_NOT_ALLOWED)
     *     $handlers; // A callable[] that should be dispatched (when FOUND)
     *     $params;   // An array of parameters matched from the URI (when FOUND)
     * }
     *
     * @param string[] $resources
     * @param string $method
     * @param string $uri
     * @return object
     */
    public function match(array $resources, $method, $uri)
    {
        $route = new \stdClass();
        $route->status = Router::NOT_FOUND;

        $handler = null;
        $urlTools = $this->urlTools;
        $table = $this->getRoutingTable($resources);

        // Find the correct handler
        if (isset($table["static"][$uri])) {
            $handler = $table["static"][$uri];
            $route->params = array();

        } else {
            foreach ($table["dynamic"] as $expr => $handlers) {
                if ($urlTools->match($expr, $uri)) {
                    $handler = $handlers;
                    $route->params = $urlTools->parameters($expr, $uri);
                    break;
                }
            }
        }

        if ($handler) {
            // GET and HEAD are treated the same by the router and the output is
            // handled by the framework further on in the dispatch process
            if (isset($handler[$method]) || $method === "HEAD" && isset($handler["GET"])) {
                // Create a new instance of the resource object and set handlers
                // to be an array of object method callables.
                $route->status = Router::FOUND;
                $route->handlers = $this->buildHandlers($handler["_name"], $handler[$method]);

            } else {
                // Provide accurate information to the User Agent about what
                // HTTP methods are supported by the route.
                $route->status = Router::METHOD_NOT_ALLOWED;
                $route->allowed = array_keys(array_slice($handler, 1));
            }
        }

        return $route;
    }

    /**
     * Returns an array containing the compiled routing information based off of
     * the provided resource objects.
     *
     * !!! WARNING !!! This will use the cached version of the table if available
     * without checking for updates. It is the end users job to ensure that this
     * cache file is kept updated.
     *
     * @param array $resources
     * @return array
     */
    public function getRoutingTable(array $resources)
    {
        if (($table = $this->cache->get("router"))) {
            return $table;
        }

        $table = array(
            "static" => array(),
            "dynamic" => array()
        );

        foreach ($resources as $resourceName) {
            $routes = $this->buildRoutingTableEntryForResource($resourceName);

            $table["static"] += $routes["static"];
            $table["dynamic"] += $routes["dynamic"];
        }
        return $table;
    }

    /**
     * Compiles an array of routing information based off of a resource object.
     *
     * @param string $resourceName
     * @return array
     */
    public function buildRoutingTableEntryForResource($resourceName)
    {
        $table = array(
            "static" => array(),
            "dynamic" => array()
        );

        $urlTools = $this->urlTools;
        $reflection = new ReflectionClass($resourceName);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            /** @var ReflectionMethod $method */
            $comment = $method->getDocComment();
            if (strpos($comment, "@route") === false) {
                continue;
            }

            $metaData = $this->parseAnnotations($comment);
            $compiled = $urlTools->compile($metaData->uri, $metaData->conditions);
            $terminated = $urlTools->terminate($metaData->uri);
            $type = "dynamic";

            // If the route does not have any regex parameters we can optimise
            // its dispatch by putting it into the static portion of the table.
            // Note that we do not use the compiled form because it means we
            // can do a direct check on the static table without needing to make
            // superfluous calls to UrlTools::terminate() in the matcher.
            if ($compiled === $terminated) {
                $type = "static";
                $compiled = $metaData->uri;
            }

            // Only create table entries where necessary to avoid bloating
            // the table.
            if (!isset($table[$type][$compiled])) {
                $table[$type][$compiled] = array();
                $table[$type][$compiled]["_name"] = $reflection->name;
            }

            // Ensure that the method will be invoked after all other middleware
            $metaData->middleware[] = $method->name;

            $table[$type][$compiled][$metaData->method] = $metaData->middleware;
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

        // Handle the route @route METHOD URI
        if (preg_match(Router::ROUTE_REGEX, $comment, $matches)) {
            $annotations["method"] = $matches[1];
            $annotations["uri"] = $matches[2];
        }

        // Handle route conditions @where PARAMETER REGEX
        if (preg_match_all(Router::CONDITION_REGEX, $comment, $matches)) {
            for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
                $annotations["conditions"][$matches[1][$i]] = $matches[2][$i];
            }
        }

        // Handle middleware @through METHOD_NAME
        if (preg_match_all(Router::THROUGH_REGEX, $comment, $matches)) {
            $annotations["middleware"] = $matches[1];
        }

        return (object)$annotations;
    }

    /**
     * Returns an array of object method callables in the format of:
     * array(array($resource, $handler)). An instance of type
     * $resourceName will be created.
     *
     * !!! WARNING !!! This method has no idea what parameters are
     * required for construction of the resource and so assumes none.
     * This can lead to runtime issues that the framework cannot
     * prevent, so ensure your resources are defined as stateless.
     *
     * @param string $resourceName
     * @param array $handlers
     * @return callable[]
     */
    public function buildHandlers($resourceName, array $handlers)
    {
        $return = array();
        $object = new $resourceName();

        foreach ($handlers as $handler) {
            $return[] = array($object, $handler);
        }
        return $return;
    }
}