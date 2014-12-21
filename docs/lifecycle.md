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
method, passing it a request and response object.

The framework creates an instance of the `Users` object because it minimises the
amount of operations that take place during the processing of the request.

