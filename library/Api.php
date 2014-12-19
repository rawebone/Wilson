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

use Exception;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Utils\Cache;

class Api
{
	/**
	 * Defines the file we should use to cache framework data.
	 *
	 * @see createCache
	 * @var string
	 */
	public $cacheFile;

	/**
	 * Defines a callable which will be used to handle any errors during dispatch.
	 * The signature of the callable is:
	 *
	 * <code>
	 * function (Request $req, Response $resp, Services $s, Exception $e) {}
	 * </code>
	 *
	 * @see defaultError
	 * @var callable
	 */
	public $error;

	/**
	 * Defines a callable which will be used when a request cannot be matched
	 * to a route. The signature of the callable is:
	 *
	 * <code>
	 * function (Request $req, Response $resp, Services $s) {}
	 * </code>
	 *
	 * @see defaultNotFound
	 * @var callable
	 */
	public $notFound;

	/**
	 * This handler is used to prepare a request and response during dispatch.
	 * The signature for this callable is:
	 *
	 * <code>
	 * function (Request $request, Response $response) {}
	 * </code>
	 *
	 * Exceptions thrown by this handler will trigger the error handler.
	 *
	 * @see error
	 * @var callable
	 */
	public $prepare;

	/**
	 * Holds all of the object names that define your API. These should be
	 * defined as:
	 *
	 * <code>
	 * $api->resources = array(
	 *     "My\Restful\ResourceA",
	 * 	   "My\Restful\ResourceB"
	 * ):
	 * </code>
	 *
	 * When the framework matches a request an instance of the object will be
	 * created automatically to fulfill the dispatch; this allows us to put off
	 * creating objects until absolutely necessary. However this does mean that
	 * the resource objects constructors cannot have any non-defaulted parameters.
	 * If your object has dependencies you should define them in a Service
	 * container.
	 *
	 * @see services
	 * @var string[]
	 */
	public $resources = array();

	/**
	 * The Service container is passed to every Controller in your application.
	 * A default is provided for you without any registered services.
	 *
	 * @see Services
	 * @var Services
	 */
	public $services;

	/**
	 * Flags that the application is being unit tested. This prevents the Api
	 * from sending responses back to the User Agent.
	 *
	 * @var bool
	 */
	public $testing = false;

	public function __construct()
	{
		$this->services = new Services();
		$this->prepare  = function () { };
		$this->error    = array($this, "defaultError");
		$this->notFound = array($this, "defaultNotFound");
	}

	/**
	 * This method can be used to give a significant performance boost to your
	 * application by pre-generating and caching the routing table. Note that
	 * this caching is NOT performed automatically and that the $api->cacheFile
	 * property needs to be set to a writable path for this to succeed.
	 *
	 * @see cacheFile
	 * @return void
	 */
	public function createCache()
	{
		$cache  = new Cache($this->cacheFile);
		$router = new Router($cache, new UrlTools());

		$table = $router->getRoutingTable($this->resources);
		$cache->set("router", $table);
	}

	/**
	 * Routes the request the handler most appropriate.
	 *
	 * @see dispatch
	 * @param Router $router
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function routeRequest(Router $router, Request $request, Response $response)
	{
		$match = $router->match(
			$this->resources,
			$request->getMethod(),
			$request->getPathInfo()
		);

		switch ($match->status) {
			case Router::FOUND:
				$request->setParams($match->params);

				// Dispatch all middleware, abort if they return boolean false
				foreach ($match->handlers as $handler) {
					// PHP5.3 does not like the array($obj, $method)() calling
					// convention so we have to use the slower call_user_func
					// method.
					$result = call_user_func(
						$handler,
						$request,
						$response,
						$this->services
					);

					if ($result === false) {
						return;
					}
				}
				break;

			case Router::NOT_FOUND:
				call_user_func($this->notFound, $request, $response, $this->services);
				break;

			case Router::METHOD_NOT_ALLOWED:
				$response->setHeader("Allow", $match->allowed);

				if ($request->getMethod() === "OPTIONS") {
					$response->setStatus(200);
				} else {
					$response->setStatus(405);

				}
				break;
		}
	}

	/**
	 * Dispatches the request and, if the Api is not under test, sends the
	 * response back to the User Agent.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function dispatch(Request $request = null, Response $response = null)
	{
		if (!$request) {
			$request = new Request();
			$request->initialise($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
		}

		if (!$response) {
			$response = new Response();
		}

		$cache  = new Cache($this->cacheFile);
		$router = new Router($cache, new UrlTools());

		try {
			call_user_func($this->prepare, $request, $response);
			$this->services->initialise($request, $response);

			$this->routeRequest($router, $request, $response);

		} catch (\Exception $exception) {
			call_user_func($this->error, $request, $response, $this->services, $exception);
		}

		if (!$this->testing) {
			$response->prepare($request);
			$response->send();
		}
	}

	/**
	 * Provides a minimal default error response, emitting a HTTP Status 500
	 * and writing the exception to the User Agent.
	 *
	 * @param Request $req
	 * @param Response $resp
	 * @param Services $s
	 * @param Exception $e
	 * @return void
	 */
	public function defaultError(Request $req, Response $resp, Services $s, Exception $e)
	{
		$resp->setStatus(500);
		$resp->setHeader("Content-Type", "text/html");
		$resp->setBody("<pre>$e</pre>");
	}

	/**
	 * Provides a minimal default not found response, emitting a HTTP Status 404
	 * and writing "Not Found" to the User Agent.
	 *
	 * @param Request $req
	 * @param Response $resp
	 * @param Services $s
	 * @return void
	 */
	public function defaultNotFound(Request $req, Response $resp, Services $s)
	{
		$resp->setStatus(404);
		$resp->setHeader("Content-Type", "text/html");
		$resp->setBody("<b>Not Found</b>");
	}
}