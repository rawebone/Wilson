# Wilson

[![Author](http://img.shields.io/badge/author-@rawebone-blue.svg?style=flat-square)](https://twitter.com/rawebone)
[![Latest Version](https://img.shields.io/github/release/rawebone/Wilson.svg?style=flat-square)](https://github.com/rawebone/Wilson/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/rawebone/Wilson/master.svg?style=flat-square)](https://travis-ci.org/rawebone/Wilson)
[![HHVM Status](https://img.shields.io/hhvm/rawebone/wilson.svg?style=flat-square)](http://hhvm.h4cc.de/package/rawebone/wilson)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/rawebone/Wilson.svg?style=flat-square)](https://scrutinizer-ci.com/g/rawebone/Wilson/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/rawebone/Wilson.svg?style=flat-square)](https://scrutinizer-ci.com/g/rawebone/Wilson)
[![Total Downloads](https://img.shields.io/packagist/dt/rawebone/wilson.svg?style=flat-square)](https://packagist.org/packages/rawebone/wilson)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/3debe9ff-28bc-46b2-ae83-a7ddcf94c576.svg?style=flat-square)](https://insight.sensiolabs.com/projects/3debe9ff-28bc-46b2-ae83-a7ddcf94c576)

Wilson is a PHP micro framework designed for simplicity and performance. It main features are: 

* Annotation based routing
* Middleware
* Service Location
* HTTP Request/Response abstraction
* HTTP Caching
* Unit Testing
* Security

Its design is based around the Slim and Symfony frameworks, combining elements of both with the
goal of creating system which is fast, correct, well documented, and simple.


## Usage

At a glance, an application in the framework looks like this:

```php
<?php

// File: public/index.php

require_once "/path/to/vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("My\Restful\ResourceA");
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
        $response->json(array("a", "b", "c"));
    }
    
    /**
     * @route GET /resource-a/{file}.md
     * @where file [a-z]+
     */
    function getRecord($request, $response, $services)
    {
        $response->setBody(get_file($request->getParam("file")));
        $response->setHeader("Content-Type", "text/plain");
    }
}

```

Look at the [wiki](https://github.com/rawebone/Wilson/wiki) for a proper guide
through the functionality.


## License and Credits

The framework is [MIT licensed](LICENSE), go hog wild. No project is an island
and so credit is owed to the following projects whose code has been purloined
or reshaped to make this one:

Project | Licence | Maintainer(s)
--------|---------|--------------
[Slim](http://www.slimframework.com) | [MIT](LICENSE.SLIM) | Josh Lockhart
[Symfony](http://symfony.com) | [MIT](LICENSE.SYMFONY) | Fabien Potencier
[phly/http](https://github.com/phly/http) | [BSD-2-Clause](LICENCE.PHLY) | Matthew Weier O'Phinney
[enygma/shield](https://github.com/enygma/shieldframework) | MIT | Chris Cornutt


## Security

If you discover any security related issues, please email rawebone@gmail.com
instead of using the issue tracker. 

