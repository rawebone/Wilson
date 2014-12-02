<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Wilson\Routing;

/**
 * Route encapsulates the result of a Routing match.
 */
class Route
{
	const FOUND = 1;
	const NOT_FOUND = 2;
	const METHOD_NOT_ALLOWED = 4;

	/**
	 * @var array
	 */
	public $allowed;

	/**
	 * @var callable[]
	 */
	public $handlers;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * @var int
	 */
	public $status;
}