<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Caching;

use Wilson\Caching\NullCache;

class NullCacheTest extends \PHPUnit_Framework_TestCase
{
	function testNullCache()
	{
		$cache = new NullCache();
		$cache->set("a", "b");

		$this->assertEquals(false, $cache->has("a"));
		$this->assertEquals(null, $cache->get("a"));
	}
}