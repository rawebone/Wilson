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

/**
 * @property string $environment
 * @property string $cachePath
 */
class Environment
{
	/**
	 * Key-value store of environmental settings.
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @param array $properties
	 */
	public function __construct(array $properties = array())
	{
		$this->properties = $this->applyDefaults($properties);
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		return (isset($this->properties[$name]) ? $this->properties[$name] : null);
	}

	public function __set($name, $value)
	{
		throw new \ErrorException("Tried to set value '$name' on " . __CLASS__);
	}

	public function production()
	{
		return $this->environment === "production";
	}

	/**
	 * @param array $properties
	 * @return array
	 */
	protected function applyDefaults(array $properties)
	{
		$defaults = array(
			"environment" => "development",
			"cachePath"   => ""
		);

		return array_merge($defaults, $properties);
	}
}