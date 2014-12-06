# Wilson

[![Build Status](https://secure.travis-ci.org/rawebone/Wilson.png?branch=perf)](http://travis-ci.org/rawebone/Wilson)

Dr James Wilson is my favourite character in House. He's  balanced, smart,
and an enabler of the genius of the title character, all the while hidden
away in the background.

This framework is built off of ideas in [AngularJS](https://angularjs.org),
[Micro](https://github.com/rawebone/Micro), [Razor](https://github.com/rawebone/Razor),
and, most importantly, [Slim](https://github.com/codeguy/Slim). It consists of:

* A lightweight Dependency Injection system
* A simple HTTP Layer based off of that in Slim framework
* Fast, annotation based routing
* Optimisation for use with web services

It provides a lightweight approach to writing RESTful web services with simple 
syntax. If you've used another Micro framework, you'll feel right at home; this 
documentation assumes familiarity with the concept of Micro frameworks and routing
in general.


## Usage

We start by defining an index file which will receive all of the traffic
for the application:

```php
<?php

require_once "/path/to/vendor/autoload.php";

use Wilson\Api;
use Wilson\Http\Response;

// This object represents our application
$api = new Api();

// We can define a handler which will be called when an error occurs while
// dispatching the request. The parameters for this callback are worked
// out by the framework, in this case they are two objects which are
// provided by the framework.
$api->error = function (Response $resp, Exception $exception)
{
    $resp->setStatus(500);
    $resp->setBody($exception);
});

// We can do the same for handling when the request doesn't match
$api->notFound = function (Response $resp)
{
    $resp->setStatus(400);
    $resp->setBody("Not found!");
});

// Wilson works out how to route traffic based on objects which contain
// our logic for handling requests. We give the framework an instance of
// them so it can dispatch the request.
$api->resources[] = new UserResource();

// We can hook into the Dependency Injection system by registering a
// service or an object instance. This allows it to be used in object
// methods later in our request handlers.
$api->injector->instance("conn", new PDO(/* ... */));

$api->injector->service("users", function (PDO $conn)
{
    return new UserService($conn);
});

// For best performance, we can cache the routing information to reduce the
// amount of work done by the framework per request. The onus is on the
// developer to decide how to call this, but the suggestion is
if ($production) {
    $api->cachePath = "/path/to/cache.php";
    
    if (!is_file($api->cachePath) {
        $api->createCache();
    }
}

// We can then run the application; this will inspect the request, match
// it to a handler defined in our resources array and send a response
// back to the client.
$api->tryDispatch();

```

To define handlers, we create an object with public methods that are annotated
in a way which lets the framework know how traffic should be directed.

```php
<?php

class UserResource
{
    /**
     * Here we define a request handler that will return all of the users for
     * the application. This receives any HTTP GET requests made to the /users
     * URL of the web server as defined by the `@route` annotation. As you can
     * see, the framework passes in an instance of the User Service object we 
     * configured in index.php.
     *
     * This abstraction of the data handling is recommended, as it effectively
     * decouples your business logic from the framework which is better for 
     * testing and migrating frameworks later on down the line. 
     *
     * @route GET /users
     */
    function getAllUsers(UserService $users)
    {
        echo $users->all();    
    }
    
    /**
     * Here we define a route which returns a single user via a GET request.
     * `{id}` in the URL is a variable which can optionally be sanitized by
     * the use of a regular expression signified in the `@where` annotation.
     * 
     * @route GET /users/{id}
     * @where id \d+
     */
    function getUser(UserService $users, Request $req)
    {
        echo $users->get($req->getParam("id"));    
    }
    
    /**
     * This is a piece of middleware. It is executed before the main handler
     * to help prepare and sanitize requests. If it returns false then the
     * framework will stop dispatching the request and send the response to
     * the client.
     */
    function parseBody(Request $req, Response $resp)
    {
        if (strpos($req->getContentType(), "application/json") === false) {
            $resp->setStatus(400);
            return false;
        }
        
        $req->setParam("json", json_decode($req->getBody(), true));
    }
    
    /**
     * Here we define a route which goes through a piece of middleware first.
     * You can define as many `@through` annotations as you want.
     *
     * @route POST /users
     * @through parseBody
     */
    function setUser(UserService $users, Request $req, Response $resp)
    {
        try {
            $resp->setStatus(201);
            echo $users->create($req->getParam("json"));
        } catch (Exception $exception) {
            $resp->setStatus(400);        
        }
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