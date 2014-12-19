# Request Lifecycle

The job of a framework can be broadly categorised as one or more of the following:

* To focus and structure your working practices
* To streamline some of the more arcane parts of a process into something simple
* To reduce the amount of work you have to do manually per request, like validating
  input, handling unsuccessful requests

Wilsons job is to streamline the process of handling HTTP requests, providing a
focused and consistent working practice and promoting safe working practices. It
does this by modeling the request-response process in a way you might not be
familiar with.


## Resources, Controllers and Middleware

Say you have a type of data that you want to expose through an API. This is, in
REST parlance, a Resource. Take a **User** for example. A user has various
fields of data and you want to create, retrieve, delete, and modify that resource
through your API. I'm not going to go into full REST semantics, because there are
lots of good books already in print for that but in your Wilson application,
you would define this object like:

```php

class User
{
    function getUsers() {}
    function getUser() {}
    function deleteUser() {}
    function updateUser() {}
    function createUser() { }
}

```

And then if you want Wilson to expose this to the world, you would go back to
the `index.php` we created in the first part of the documentation and add in
the following line:

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("User");
$api->dispatch();

```

Neat! But the resource doesn't do a lot and the framework won't know what to do
with it. This is because Wilson relies on you annotating the methods of the
object with instructions that it can use to direct traffic. For example, if you
run this in your browser, you will end up getting a 404 Not Found response back.

So say we want to get a list of users back, we need to change things a bit:

```php

class User
{
    /**
     * @route GET /users/
     */
    function getUsers()
    {
        echo "Hello!";
    }
    
    // ...
}

```

Now when we hit this in our browser like `http://localhost/users/` we get a
200 Okay response back that says "Hello!". Cool!

What we have added to this example is an annotation. In the DocComment above
the getUsers() method we have put `@route GET /users/` which the framework
reads and so, when making a GET request to /users/, it knows that that a User
object instance should be created and the getUsers() method called.

Now we could stop here but as I noted above Wilson models the HTTP Request
and Response, taking some of the pains out of this for you. 
