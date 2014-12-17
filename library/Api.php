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
use Wilson\Utils\Injector;

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
	 *
	 * A service called "exception" is available to this handler.
	 *
	 * @var callable
	 */
	public $error;

	/**
	 * The Injector is at the core of the Wilson framework and can be used to
	 * define singletons in your application, allowing you to keep your code
	 * testable but also fast.
	 *
	 * @var Injector
	 */
	public $injector;

	/**
	 * Defines a callable which will be used when a request cannot be matched
	 * to a route.
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
	 * @param Injector $injector
	 */
	public function __construct(Injector $injector = null)
	{
		$this->injector = $injector ?: new Injector();
		$this->buildInjector();

		$this->error = function (Response $resp, Exception $exception)
		{
			$resp->setStatus(500);
			$resp->setBody($exception);
		};

		$this->notFound = function (Response $resp)
		{
			$resp->setStatus(404);
		};
	}

	/**
	 * Processes over all of the registered resources and creates the
	 * routing table. This can offer a significant time saving when
	 * dispatching the request.
	 */
	public function createCache()
	{
		/** @var Cache $cache */
		$cache = $this->injector->resolve("_cache");

		/** @var Router $router */
		$router = $this->injector->resolve("_router");

		$table = $router->getTable($this->resources);
		$cache->set("router", $table);
	}

	/**
	 * Dispatches the request and sends the response.
	 *
	 * @param Router $_router
	 * @param Request $req
	 * @param Response $resp
	 * @return void
	 */
	public function dispatch(Router $_router, Request $req, Response $resp)
	{
		$match = $_router->match(
			$this->getResources(),
			$req->getMethod(),
			$req->getPathInfo()
		);

		switch ($match->status) {
			case Router::NOT_FOUND:
				$this->injector->inject($this->notFound);
				break;

			case Router::METHOD_NOT_ALLOWED:
				$resp->setHeader("Allow", $match->allowed);

				if ($req->getMethod() === "OPTIONS") {
					$resp->setStatus(200);
				} else {
					$resp->setStatus(405);

				}
				break;

			case Router::FOUND:
				$req->setParams($match->params);

				// Traverse the middleware and handler, aborting if any
				// of handlers fail.
				foreach ($match->handlers as $handler) {
					if ($this->injector->inject($handler) === false) {
						return;
					}
				}
				break;
		}

		$resp->send();
	}

	/**
	 * Calls the dispatcher, directing any errors to the error handler.
	 *
	 * @return void
	 */
	public function tryDispatch()
	{
		try {
			$this->injector->inject(array($this, "dispatch"));

		} catch (\Exception $exception) {
			$this->injector->instance("exception", $exception);
			$this->injector->inject($this->error);
		}
	}

	/**
	 * Defines the basic handling for the framework.
	 *
	 * @return void
	 */
	protected function buildInjector()
	{
		$this->injector->instance("_api", $this);
		$this->injector->instance("_injector", $this->injector);

		$this->injector->factory("_ut", function ()
		{
			return new UrlTools();
		});

		$this->injector->factory("_cache", function (Api $_api)
		{
			return new Cache($_api->cachePath);
		});

		$this->injector->factory("_router", function ($_cache, $_ut)
		{
			return new Router($_cache, $_ut);
		});

		$this->injector->factory("req", function ()
		{
			return new Request($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
		});

		$this->injector->factory("resp", function ($req)
		{
			return new Response($req);
		});
	}

	/**
	 * Returns an array of class_name -> object.
	 *
	 * @return array
	 */
	protected function getResources()
	{
		$resources = array();
		foreach ($this->resources as $resource) {
			$resources[get_class($resource)] = $resource;
		}
		return $resources;
	}
}