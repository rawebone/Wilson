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

use Wilson\Cache;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
	function testStore()
	{
		$file = __DIR__ . "/file.php";

		$cache = new Cache($file);
		$cache->set("a", "b");
		unset($cache);

		$this->assertEquals(true, is_file($file));

		$content = include $file;
		$this->assertArrayHasKey("a", $content);

		unlink($file);
	}

	function testLoad()
	{
		$file = __DIR__ . "/file.php";
		file_put_contents($file, "<?php return array('a' => 'b');");

		$cache = new Cache($file);
		$this->assertEquals("b", $cache->get("a"));
		unset($cache);

		unlink($file);
	}
}