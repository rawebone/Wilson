# Wilson

[![Build Status](https://secure.travis-ci.org/rawebone/Wilson.png?branch=perf)](http://travis-ci.org/rawebone/Wilson)

Dr James Wilson is my favourite character in House. He's  balanced, smart,
and an enabler of the genius of the title character, all the while hidden
away in the background.

This framework is built for high performance RESTful web services; to do so
I've had to leave out the thrills in terms of the setup code but the pay of
is good performance and:

* Annotation based routing
* A lightweight HTTP abstraction based off of that available in
  [Slim](http://www.slimframework.com/)
* A Service Container mechanism


## Usage

```php
<?php

require_once "/path/to/vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array( "My\Restful\ResourceA" );
$api->tryDispatch();

```

```php
<?php

namespace My\Restful;

use Wilson\Services;
use Wilson\Http\Request;
use Wilson\Http\Response;

class Resource
{
    /**
     * @route GET /resource-a/
     */
    function collection(Request $request, Response $response, Services $services)
    {
    
    }
}

```

## TODO

* Improve Test Coverage
* Add Cookie handling from Slim
* Implement HTTP Caching
* Improve documentation


## License

[MIT License](LICENSE), go hog wild.