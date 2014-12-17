<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Utils;

/**
 * Injector provides a simple Service Injection mechanism. Service Injection
 * allows us to define dependencies easily and lazily which can then be resolved
 * on an ad-hoc basis.
 */
class Injector
{
	/**
	 * @var callable[]
	 */
	protected $factories = array();

	/**
	 * @var callable[]
	 */
	protected $extensions = array();

	/**
	 * @var mixed[]
	 */
	protected $resolved = array();

	/**
	 * @var string[]
	 */
	protected $beingResolved = array();

	/**
	 * Returns whether a service has been defined.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function defined($name)
	{
		return isset($this->factories[$name]) || isset($this->resolved[$name]);
	}

	/**
	 * Resolves the services for and executes the given callable. The result of
	 * the executed callable is returned to the caller, if applicable.
	 *
	 * @param callable $fn
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function inject($fn)
	{
		return call_user_func_array($fn, $this->dependencies($fn));
	}

	/**
	 * Returns the dependencies for a function. These can then be invoked
	 * with call_user_func_array().
	 *
	 * @param callable $fn
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public function dependencies($fn)
	{
		$args = array();
		foreach ($this->requires($fn) as $dependency) {
			$args[$dependency] = $this->resolve($dependency);
		}

		return $args;
	}

	/**
	 * Registers a service factory with the Injector. A factory is a
	 * callable which returns the configured service which allows us
	 * to lazy load our and configure as required. This is slower than
	 * registering an instance unless the service is IO bound.
	 *
	 * @param string $name
	 * @param callable $fn
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function factory($name, $fn)
	{
		if (!is_callable($fn)) {
			throw new \InvalidArgumentException("\$fn is expected to be a callable");
		}

		if (isset($this->resolved[$name])) {
			throw new \RuntimeException("Service '$name' cannot be set as it has already been resolved");
		}

		$this->factories[$name] = $fn;
		return $this;
	}

	/**
	 * Allows for a service to be extended with new functionality or configuration.
	 * Multiple extensions can be registered for services.
	 *
	 * @param string $name
	 * @param callable $fn
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function extend($name, $fn)
	{
		if (!is_callable($fn)) {
			throw new \InvalidArgumentException("\$fn is expected to be a callable");
		}

		if (!isset($this->extensions[$name])) {
			$this->extensions[$name] = array();
		}

		$this->extensions[$name][] = $fn;
		return $this;
	}

	/**
	 * Registers an instance of an object into the resolved cache.
	 *
	 * @param string $name
	 * @param mixed $object
	 * @throws \InvalidArgumentException
	 */
	public function instance($name, $object)
	{
		if ($this->defined($name)) {
			throw new \InvalidArgumentException("'$name' is already registered");
		}

		$this->resolved[$name] = $object;
		return $this;
	}

	/**
	 * Resolves a service by name and returns the resultant object.
	 *
	 * @param string $name
	 * @return mixed
	 * @throws \LogicException
	 * @throws \InvalidArgumentException
	 */
	public function resolve($name)
	{
		if (isset($this->resolved[$name])) {
			return $this->resolved[$name];
		}

		if (!$this->defined($name)) {
			throw new \InvalidArgumentException("Service $name cannot be resolved as it is not defined");
		}

		if (isset($this->beingResolved[$name])) {
			$last = end($this->beingResolved);
			throw new \LogicException("Cyclic Dependency found - $last depends on $name which is currently being resolved");
		}

		$this->beingResolved[$name] = true;

		$factory = $this->factories[$name];
		$args = $this->dependencies($factory);

		unset($this->beingResolved[$name]);

		$resolved = $this->resolved[$name] = call_user_func_array($factory, $args);

		if (isset($this->extensions[$name])) {
			foreach ($this->extensions[$name] as $extension) {
				$extension($resolved);
			}
		}

		return $resolved;
	}

	/**
	 * Returns the names of the services required by a callable.
	 *
	 * @param callable $fn
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public function requires($fn)
	{
		if (is_array($fn)) {
			$reflection = new \ReflectionMethod($fn[0], $fn[1]);
		} else if ($fn instanceof \Closure || is_string($fn)) {
			$reflection = new \ReflectionFunction($fn);
		} else if (is_object($fn) && method_exists($fn, "__invoke")) {
			$reflection = new \ReflectionMethod($fn, "__invoke");
		} else {
			throw new \InvalidArgumentException("\$fn is expected to be a callable");
		}

		$required = array();

		foreach ($reflection->getParameters() as $parameter) {
			$required[] = $parameter->getName();
		}

		return $required;
	}
}