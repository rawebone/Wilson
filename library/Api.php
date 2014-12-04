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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Api
{
	/**
	 * @var string
	 */
	public $cachePath;

	/**
	 * @var callable
	 */
	public $error;

	/**
	 * @var Injector
	 */
	public $injector;

	/**
	 * @var callable
	 */
	public $notFound;

	/**
	 * @var object[]
	 */
	public $resources = array();

	public function __construct()
	{
		$this->injector = new Injector();

		$this->error = function ()
		{

		};

		$this->notFound = function ()
		{

		};
	}

	public function run()
	{
		$req  = Request::createFromGlobals();
		$resp = new Response();

		$resp->prepare($req);

		$this->injector->instance("req", $req);
		$this->injector->instance("resp", $resp);

		$this->tryDispatch($req, $resp);

		$resp->send();
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 */
	protected function dispatch(Request $request, Response $response)
	{
		$route = $this->router->match(
			$resources,
			$request->getMethod(),
			$request->getPathInfo()
		);

		switch ($route->status) {
			case Route::NOT_FOUND:
				$this->injector->inject($this->notFound);
				break;

			case Route::METHOD_NOT_ALLOWED:
				$response->headers->set("Allow", $route->allowed);

				if ($request->getMethod() === "OPTIONS") {
					$response->setStatusCode(200);
				} else {
					$response->setStatusCode(405);

				}
				break;

			case Route::FOUND:
				$request->request->add($route->params);

				foreach ($route->handlers as $handler) {
					if (!$this->injector->inject($handler)) {
						return;
					}
				}
				break;
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 */
	protected function tryDispatch(Request $request, Response $response)
	{
		try {
			$this->dispatch($request, $response);

		} catch (\Exception $exception) {
			$this->injector->instance("exception", $exception);
			$this->injector->inject($this->error);
		}
	}
}