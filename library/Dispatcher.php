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

use FastRoute\Dispatcher as FastDispatcher;
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
	 * @var Environment
	 */
	protected $environment;

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
	public function notFound($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Argument should be callable");
		}

		$this->notFound = $callable;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function dispatch(Request $request, Response $response)
	{
		$route = $this->router->dispatch($request->getMethod(), $request->getPathInfo());

		switch ($route[0]) {
			case FastDispatcher::NOT_FOUND:
				$this->injector->inject($this->notFound);
				break;

			case FastDispatcher::METHOD_NOT_ALLOWED:
				$response->setStatusCode(405);
				$response->headers->set("Allow", $route[1]);
				break;

			case FastDispatcher::FOUND:
				$request->request->add($route[2]); // URL Params
				$this->injector->inject($route[1]); // The callback
				break;
		}
	}
}