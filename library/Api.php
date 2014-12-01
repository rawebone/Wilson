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
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Caching\NullCache;
use Wilson\Caching\FileCache;
use Wilson\Caching\CacheInterface;
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
	 * @var Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var Injector
	 */
	protected $injector;

	/**
	 * @var <string, object>[]
	 */
	protected $resources = array();

	/**
	 * @param Dispatcher $dispatcher
	 * @param Injector $injector
	 */
	public function __construct(Dispatcher $dispatcher,	Injector $injector)
	{
		$this->dispatcher = $dispatcher;
		$this->injector   = $injector;
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

		$this->resources[get_class($resource)] = $resource;
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

		$this->dispatcher->error($callable);
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

		$this->dispatcher->notFound($callable);
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

		$this->dispatcher->tryDispatch($this->resources, $req, $resp);

		$resp->send();
	}

	/**
	 * Creates a new instance of the API.
	 *
	 * @param array $configuration
	 * @return static
	 */
	public static function createServer(array $configuration = array())
	{
		$environment = new Environment($configuration);

		if ($environment->production()) {
			$cache = new FileCache($environment->cachePath);

		} else {
			$cache = new NullCache();
		}

		$injector   = new Injector($cache);
		$router     = new Router($cache, new UrlTools());
		$dispatcher = new Dispatcher($injector, $router);

		$dispatcher->notFound(function (Response $resp)
		{
			$resp->setStatusCode(404);
		});

		$dispatcher->error(function (Environment $environment, Exception $exception, Response $resp)
		{
			$resp->setStatusCode(503);

			if (!$environment->production()) {
				$resp->setContent("<pre>$exception</pre>");
			}
		});

		$injector->instance("environment", $environment);

		return new static($dispatcher, $injector);
	}
}