# Wilson

[![Author](http://img.shields.io/badge/author-@rawebone-blue.svg?style=flat-square)](https://twitter.com/rawebone)
[![Latest Version](https://img.shields.io/github/release/rawebone/Wilson.svg?style=flat-square)](https://github.com/rawebone/Wilson/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/rawebone/Wilson/master.svg?style=flat-square)](https://travis-ci.org/rawebone/Wilson)
[![HHVM Status](https://img.shields.io/hhvm/rawebone/wilson.svg?style=flat-square)](http://hhvm.h4cc.de/package/rawebone/wilson)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/rawebone/Wilson.svg?style=flat-square)](https://scrutinizer-ci.com/g/rawebone/Wilson/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/rawebone/Wilson.svg?style=flat-square)](https://scrutinizer-ci.com/g/rawebone/Wilson)
[![Total Downloads](https://img.shields.io/packagist/dt/league/period.svg?style=flat-square)](https://packagist.org/packages/league/period)

Wilson is a PHP Micro framework aimed at providing methodologies associated with
full stack frameworks without the performance penalties, those being:

* Annotation based routing
* Service Location
* A lightweight HTTP abstraction based off of that available in
  [Slim](http://www.slimframework.com/)


## Usage

At a glance, an application in the framework looks like this:

```php
<?php

// File: public/index.php

require_once "/path/to/vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array( "My\Restful\ResourceA" );
$api->dispatch();

```

```php
<?php

// File: src/My/Restful/ResourceA.php

namespace My\Restful;

class ResourceA
{
    /**
     * @route GET /resource-a/
     */
    function getCollection($request, $response, $services)
    {
        $data = array("a", "b", "c");
        
        $response->setStatus(200);
        $response->setHeader("Content-Type", "application/json");
        $response->setBody(json_encode($data));
    }
}

```

Look at the [docs](docs/index.md) for a proper guide through the functionality.


## TODO

* Improve Test Coverage
* Add Cookie handling from Slim
* Add File handling
* Complete implementation of HTTP Caching


## Credits

* Josh Lockhart and other contributors to [Slim Framework](http://www.slimframework.com)
* Fabien Potencier and other contributors to [Symfony](http://symfony.com)


## License

[MIT License](LICENSE), go hog wild.