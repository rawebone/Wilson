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

use Wilson\Routing\Route;
use Wilson\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wilson\Injection\Injector;

class Dispatcher
{
	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var callable
	 */
	protected $error;

	/**
	 * @var Injector
	 */
	protected $injector;

	/**
	 * @var callable
	 */
	protected $notFound;

	/**
	 * @param Injector $injector
	 * @param Router $router
	 */
	public function __construct(Injector $injector, Router $router)
	{
		$this->injector = $injector;
		$this->router   = $router;
	}

	/**
	 * @param callable $callable
	 */
	public function error($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Argument should be callable");
		}

		$this->error = $callable;
	}

	/**
	 * @param callable $callable
	 */
	public function notFound($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Argument should be callable");
		}

		$this->notFound = $callable;
	}

	/**
	 * @param array $resources
	 * @param Request $request
	 * @param Response $response
	 */
	public function dispatch(array $resources, Request $request, Response $response)
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
	 * @param array $resources
	 * @param Request $request
	 * @param Response $response
	 */
	public function tryDispatch(array $resources, Request $request, Response $response)
	{
		try {
			$this->dispatch($resources, $request, $response);

		} catch (\Exception $exception) {
			$this->injector->instance("exception", $exception);
			$this->injector->inject($this->error);
		}
	}
}