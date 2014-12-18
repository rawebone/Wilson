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

use Wilson\Http\Request;
use Wilson\Http\Response;

/**
 * This is a basic service container that allows for lazy loading of objects.
 * An instance of this object will be passed to the Controller and other
 * handlers in the framework.
 *
 * This object should be extended with getters in the form of getConnection,
 * and the service can be gotten by calling $service->connection.
 */
class Services
{
	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var object[]
	 */
	protected $instances = array();

	/**
	 * Sets the request and the response which can be used by other objects.
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function initialise(Request $request, Response $response)
	{
		$this->request   = $request;
		$this->response  = $response;
		$this->instances = array();
	}

	/**
	 * Returns a service identified by name.
	 *
	 * @param string $name
	 * @return object
	 * @throws \ErrorException
	 */
	public function __get($name)
	{
		if (isset($this->instances[$name])) {
			return $this->instances[$name];
		}

		$factory = "get" . ucfirst($name);
		if (!method_exists($this, $factory)) {
			throw new \ErrorException("Unknown service '$name'");
		}

		return $this->instances[$name] = $this->$factory();
	}
}