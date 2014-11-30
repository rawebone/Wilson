<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Injection;

/**
 * Provider instances can offer services that can be injected into routes.
 */
interface ProviderInterface
{
	/**
	 * Registers services with the injector.
	 *
	 * @param Injector $injector
	 * @return void
	 */
	function register(Injector $injector);
}