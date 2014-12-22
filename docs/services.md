# Services

When working on an application you quickly find yourself needing to share 
configured object instances between different parts of your codebase. Some
systems like Laravel and Symfony provide **Dependency Injection** where your
configured object is passed either to the constructor or a setter of the
object dependent upon it.

Other systems like Slim and Silex use a system called **Service Location** and
let the code being called pull in any dependencies it needs. Wilson uses a
variation of this optimised for performance.


## The Service Container

The framework provides an object called `Wilson\Services` which provides the
basic Service Container mechanism. Essentially, you extend out from this
object and create methods, called **Factories**, which return configured object
instances. The underlying `Services` object then handles creation and caching
of these objects. For example, say we want to share a Database Connection around:

```php

class Services extends Wilson\Services
{
    protected function getConnection()
    {
        return new PDO(/* ... */);
    }
}

```

You can then access this connection as follows:

```php

$services = new Services();
$connection = $services->connection;

```

It is recommended that the Factory be made protected to avoid them being called
directly. Additionally, it is recommended that you use `@property` annotations
for the class to document what services are available in your IDE:

```php

/**
 * @property PDO $connection
 */
class Services extends Wilson\Services
{
    protected function getConnection()
    {
        return new PDO(/* ... */);
    }
}

```


## Using the Service Container

The framework needs to know to use your instance of the Service Container. To
do this, we give an instance to the `Api` object that we created initially in
our `index.php`:

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("Users");
$api->services  = new Services();
$api->dispatch();

```

Great, but now you might be wondering how your Controllers get access to
these services you've created. If you think back to the [Lifecycle](lifecycle.md)
document you'll remember that every Controller and Middleware Controller
is automatically passed an instance of the `Request` and `Response` objects.
They also receive an instance of the `Service` object automatically:

```php

    /**
     * @route GET /users/{id}
     * @where id \d+
     * @through authenticate
     */
    function getUser(Request $request, Response $response, Services $services)
    {
        $connection = $services->connection;
        $data = $this->magicallyReturnData($request->getParam("id"));
        
        // ...
    }

```


## Next: [Testing](testing.md)
