# Testing

There are a couple of types of testing you can choose to use with Wilson,
depending on your preference:

1. Controller Testing
2. Functional Testing

The later is recommended and is supported out of the box.


## Controller Testing
  
In this variation, you create a test which allows you to look only at the
functionality of the individual Controller. You end up writing code like:

```php

use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Services;

class UsersTest extends PHPUnit_Framework_TestCase
{
    function testGetUsers()
    {
        $request  = new Request();
        $response = new Response();
        $services = new Services();
        
        $resource = new Users();
        $resource->getUsers($request, $response, $services);
        
        $this->assertEquals(200, $response->getStatus());
    }
}

```

This leads to some quite verbose tests, even more so where spies or mocks are
involved. Additionally, if you setup state during other Middlewares, then you
do not get a realistic test. As such, you get a better quality of testing with
...


## Functional Testing

A functional test is where you make a request through the framework itself,
allowing you to ensure that requests get where they should and provide the
ability to inspect the result. There is a little more in the way of setup
but this pays off:

```php

class TestCase extends Wilson\Utils\TestCase
{
    protected function getApi()
    {
        $testing = true;
        return require_once "/path/to/index.php";
    }
     
}

```

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("Users");
$api->services  = new Services();

if (isset($testing)) {
    return $api;
}

$api->dispatch();

```

```php

class UsersTest extends TestCase
{
    function testGetUsers()
    {
        $response = $this->call("GET", "/users/");
        
        $this->assertEquals(200, $response->getStatus());
    }
}

```


## Next: [Performance](performance.md)
