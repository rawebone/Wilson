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
	 * Defines the path where we can cache framework data.
	 *
	 * @var string
	 */
	public $cachePath;

	/**
	 * Defines a callable which will be used to handle any errors during dispatch.
	 * The signature of the callable is:
	 *
	 * function (Request $req, Response $resp, Services $s, Exception $e)
	 *
	 * @var callable
	 */
	public $error;

	/**
	 * Defines a callable which will be used when a request cannot be matched
	 * to a route. The signature of the callable is:
	 *
	 * function (Request $req, Response $resp, Services $s)
	 *
	 * @var callable
	 */
	public $notFound;

	/**
	 * Holds all of the objects that define your API.
	 *
	 * @var object[]
	 */
	public $resources = array();

	/**
	 * The Service container is passed to every Controller in your application.
	 * A default is provided for you without any registered services.
	 *
	 * @var Services
	 */
	public $services;

	/**
	 * Flags that the application is being unit tested.
	 *
	 * @var bool
	 */
	public $testing = false;

	public function __construct()
	{
		$this->services = new Services();

		$this->error = function (Request $request, Response $response,
								 Services $services, Exception $exception)
		{
			$response->setStatus(500);
			$response->setBody($exception);
		};

		$this->notFound = function (Request $request, Response $response,
									Services $services)
		{
			$response->setStatus(404);
		};
	}

	/**
	 * Processes over all of the registered resources and creates the
	 * routing table. This can offer a significant time saving when
	 * dispatching the request.
	 */
	public function createCache()
	{
		$cache  = new Cache($this->cachePath);
		$router = new Router($cache, new UrlTools());

		$table = $router->getTable($this->resources);
		$cache->set("router", $table);
	}

	/**
	 * Dispatches the request and sends the response.
	 *
	 * @param Router $router
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function dispatch(Router $router, Request $request, Response $response)
	{
		$match = $router->match(
			$this->resources,
			$request->getMethod(),
			$request->getPathInfo()
		);

		switch ($match->status) {
			case Router::FOUND:
				$request->setParams($match->params);

				// Traverse the middleware and handler, aborting if any
				// of handlers fail.
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
				$notFound = $this->notFound;
				$notFound($request, $response, $this->services);
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

		if (!$this->testing) {
			$response->send();
		}
	}

	/**
	 * Calls the dispatcher, directing any errors to the error handler.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function tryDispatch(Request $request = null, Response $response = null)
	{
		if (!$request) {
			$request = new Request();
			$request->initialise($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
		}

		if (!$response) {
			$response = new Response($request);
		}

		$cache  = new Cache($this->cachePath);
		$router = new Router($cache, new UrlTools());

		try {
			$this->dispatch($router, $request, $response);

		} catch (\Exception $exception) {
			$error = $this->error;
			$error($request, $response, $this->services, $exception);
		}
	}
}