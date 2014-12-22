<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


///
/// This file can be used to compile the framework down into a single file
/// for performance.
///

$path = realpath(__DIR__ . "/../library/");
$map  = array();
build_map($map, $path, $path);

$buffer = "<?php" . PHP_EOL;

write_license($buffer);

foreach ($map as $ns => $data) {

	$buffer .= "namespace " . $ns . " {" . PHP_EOL . PHP_EOL;
	foreach (array_unique($data["imports"]) as $import) {
		$buffer .= "use $import;" . PHP_EOL;
	}

	$buffer .= PHP_EOL;

	foreach ($data["classes"] as $class) {
		$buffer .= $class . PHP_EOL . PHP_EOL;
	}
	$buffer .= "}" . PHP_EOL . PHP_EOL;
}

file_put_contents(__DIR__ . "/../compiled.php", $buffer);

function write_license(&$buffer)
{
	$buffer .= <<<HEADER
/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

HEADER;
}

function build_map(array &$map, $path, $lastPath)
{
	foreach (new DirectoryIterator($path) as $file) {

		/** @var SplFileInfo $file */
		if (in_array($file->getBasename(), array(".", ".."))) {
			continue;
		}

		if ($file->isDir()) {
			build_map($map, $file->getRealPath(), $path);

		} else {
			echo "> " . str_replace($lastPath . DIRECTORY_SEPARATOR, "", $file->getRealPath()), PHP_EOL;

			$contents = file_get_contents($file->getRealPath());

			$nsStart = strpos($contents, "namespace ") + 10;
			$nsStop  = strpos($contents, ";", $nsStart);

			$ns = substr($contents, $nsStart, $nsStop - $nsStart);
			$body = trim(substr($contents, $nsStop + 1));

			if (!isset($map[$ns])) {
				$map[$ns] = array();
				$map[$ns]["classes"] = array();
				$map[$ns]["imports"] = array();
			}

			$catchImport = function (array $match) use ($ns, &$map)
			{
				$map[$ns]["imports"][] = $match[1];
				return "";
			};

			$body = preg_replace_callback("/^use ([^;]+);$/m", $catchImport, $body);

			$map[$ns]["classes"][] = trim($body);
		}
	}
}
