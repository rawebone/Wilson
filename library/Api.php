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
use Wilson\Injection\Injector;
use Wilson\Injection\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Api represents
 */
class Api
{
	/**
	 * @var Environment
	 */
	protected $environment;

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
	 * @var array
	 */
	protected $resources = array();

	/**
	 * @param Environment $environment
	 * @param Injector $injector
	 */
	public function __construct(Environment $environment, Injector $injector)
	{
		$this->environment = $environment;
		$this->injector = $injector;

		$this->injector->instance("environment", $environment);

		$this->notFound = function (Response $resp)
		{
			$resp->setStatusCode(404);
		};

		$this->error = function (Exception $exception, Response $resp) use ($environment)
		{
			$resp->setStatusCode(503);

			if (!$environment->production()) {
				$resp->setContent("<pre>" . $exception . "</pre>");
			}
		};
	}

	/**
	 * @param object $resource
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function attach($resource)
	{
		if (!is_object($resource)) {
			throw new \InvalidArgumentException(sprintf(
				"\$resource should be an object, %s given",
				gettype($resource)
			));
		}

		$this->resources[] = $resource;
		return $this;
	}

	/**
	 * @param ProviderInterface $provider
	 * @return $this
	 */
	public function services(ProviderInterface $provider)
	{
		$provider->register($this->injector);
		return $this;
	}

	/**
	 * The handler to be called when an error occurs.
	 *
	 * @param callable $callable
	 * @return $this
	 */
	public function error($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Argument should be callable");
		}

		$this->error = $callable;
		return $this;
	}

	/**
	 * The handler to be called when a route does not match.
	 *
	 * @param callable $callable
	 * @return $this
	 */
	public function notFound($callable)
	{
		if (!is_callable($callable)) {
			throw new \InvalidArgumentException("Argument should be callable");
		}

		$this->notFound = $callable;
		return $this;
	}

	/**
	 * @param Request $req
	 * @param Response $resp
	 */
	public function run(Request $req = null, Response $resp = null)
	{
		$req = $req ?: Request::createFromGlobals();
		$resp = $resp ?: new Response();

		$this->injector->instance("req", $req);
		$this->injector->instance("resp", $resp);

		try {
			$router = new Router($this->resources, $this->environment->cachePath);
			$dispatcher = new Dispatcher($this->injector, $router);
			$dispatcher->notFound($this->notFound);

			$dispatcher->dispatch($req, $resp);

		} catch (Exception $exception) {
			$this->injector->instance("exception", $exception);
			$this->injector->inject($this->error);
		}

		$resp->send();
	}

	/**
	 * Creates a new instance of the
	 *
	 * @param array $configuration
	 * @return static
	 */
	public static function createServer(array $configuration = array())
	{
		return new static(new Environment($configuration), new Injector());
	}
}