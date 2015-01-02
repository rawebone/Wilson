# Request Lifecycle

Wilson works by intercepting HTTP **Requests**, routes that request to an
appropriate **Controller** and then sends a **Response** back to the client.
Lets start at the start with the `index.php` file we created in 
[Installation and Setup](installing.md):

```php
<?php

require_once "vendor/autoload.php"

$api = new Wilson\Api();
$api->dispatch();

```

Here we create an instance of an `Api` object, which is the entry point for the
framework. The call we make to `dispatch` tells the framework to intercept the
request and send the response. Neat! So that leaves us with **Controllers**.


## Controllers

A **Controller** is a method of an object which processes a **Request** and prepares
a **Response**. The object which contains Controllers is referred to as a 
**Resource**, but we'll come to that later. Say we have a series of users that
we want to expose through our API, we would write a Controller such as:

```php

use Wilson\Http\Request;
use Wilson\Http\Response;

class Users
{
    /**
     * @route GET /users/
     */
    function getUsers(Request $request, Response $response)
    {
        $data = $this->magicallyReturnData();
        
        $response->setStatus(200);
        $response->setHeader("Content-Type", "application/json");
        $response->setBody(json_encode($data));
    }
}

```

We then have to tell Wilson to consider the User object when dispatching the 
request:

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("Users");
$api->dispatch();

```

Now, if we were to spool up a configured web server and go to, say,
`http://localhost/users/` we would then expect to see some JSON output. The
framework looks at the request and sees that the query string is `/users` and
that the HTTP method is `GET`. It looks over the annotations that are supplied
in the exposed resources and sees that `getUsers` can handle this type of
request. So it creates an instance of the `Users` object and calls the `getUsers`
method, passing it a request and response object. Once the call to `getUsers`
completes the response is then prepared and sent back to the User Agent.

> The framework creates an instance of the `Users` object because it minimises the
> amount of objects that are held in memory during the processing of the request
> which helps reduce your applications footprint. The knock on effect is that the
> Resource object cannot have any constructor arguments and as such any
> dependencies have to be used with the [Service Container](services.md).

So far so simples. However, we now want to return a single user through our API
with a URI like `/users/1` - how do we do this? Wilson provides the ability to
capture values in URIs called **Parameters**:

```php

use Wilson\Http\Request;
use Wilson\Http\Response;

class Users
{
    /**
     * @route GET /users/
     */
    function getUsers(Request $request, Response $response)
    {
        $data = $this->magicallyReturnData();
        
        $response->setStatus(200);
        $response->setHeader("Content-Type", "application/json");
        $response->setBody(json_encode($data));
    }
    
    /**
     * @route GET /users/{id}
     */
    function getUser(Request $request, Response $response)
    {
        $data = $this->magicallyReturnData($request->getParam("id"));
        
        // ...
    }
}

```

Cool! But not necessarily secure because `id` could be anything; as such the
framework provides another annotation which can be used in conjunction with
Parameters called **Conditions** that allows you more control over your input:

```php

    /**
     * @route GET /users/{id}
     * @where id \d+
     */
    function getUser(Request $request, Response $response)
    {
        $data = $this->magicallyReturnData($request->getParam("id"));
        
        // ...
    }

```

We are now saying that the Parameter `id` must meet the regular expression of
`\d+` or in other words that `id` __must__ be an integer of any length.
 
> It is worth keeping in mind when developing an API that using integer
> based IDs can enable third parties to easily scrape your data and so
> should be used with caution.


### Middleware

Often when working with an API you find yourself performing repetitive tasks
such as authentication, content acceptance, et al. You might find yourself
writing code like:

```php

    /**
     * @route GET /users/{id}
     * @where id \d+
     */
    function getUser(Request $request, Response $response)
    {
        if (!$this->authenticate()) {
            $response->setStatus(401);
            return
        }
    
        $data = $this->magicallyReturnData($request->getParam("id"));
        
        // ...
    }

```

This violates DRY and distracts from the real problem your Controller is trying
to solve. As such, Wilson provides a **Middleware** system that allows you easily
reuse code. For example:


```php

use Wilson\Http\Request;
use Wilson\Http\Response;

trait Middleware
{
    function authenticate(Request $request, Response $response)
    {
        if ($request->getUsername() === "John") {
            $response->setStatus(401);
            return false;
        }
    }
}

```

```php

    use Middleware;

    /**
     * @route GET /users/{id}
     * @where id \d+
     * @through authenticate
     */
    function getUser(Request $request, Response $response)
    {
        $data = $this->magicallyReturnData($request->getParam("id"));
        
        // ...
    }

```

So what we have done here is create a trait to hold all of our Middleware, added
a **Middleware Controller** called authenticate which **returns boolean false** if
the request process should abort, and added a `@through` annotation to the
`getUsers` Controller. Now when the framework dispatches the request it will put
the request through the `authenticate` method first.

We can assign as much Middleware as we require and the framework will process
each in turn until one returns boolean false or there are no more Controllers
left. A Middleware Controller is exactly the same as a Controller, the only
difference being that a Middleware Controller does not have a `@route`
annotation.


## Request and Response Headers

Please be aware that header names **are not normalised** between requests and
responses. This is because normalising the names of headers in the `$_SERVER`
superglobal can add a substantial amount of time to the request add so we
instead use the SAPI appropriate names, i.e.:

```php

$req->getHeader("HTTP_CONTENT_TYPE");
$resp->setHeader("Content-Type", "text/html");

```


## Advanced Handling 

### Preparing Requests and Responses

The framework allows you to setup headers/parameters against the request/
responses prior to the routing of the request.

```php

$api->prepare = function (Request $request, Response $response)
{
    // ...
};

```


### Error Handling

If an exception is thrown during the dispatch of the request, the
framework exposes a slot to handle this event:

```php

$api->error = function (Request $req, Response $resp, Services $s, Exception $e)
{
    // ...
};

```


### Not Found Handling

If a request cannot be routed, the framework exposes a slot to handle this
event:

```php

$api->notFound = function (Request $request, Response $response, Services $services)
{
    // ...
};

```


## Next: [Services](services.md)
