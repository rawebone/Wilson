# Wilson

Dr James Wilson is my favourite character in House. He's  balanced, smart,
and an enabler of the genius of the title character, all the while hidden
away in the background.

These are also the kinds of qualities that I seek out in the code I use- I
want a framework that makes reasonable decisions about how a request should
be handled without getting in my way or slowing me down. This framework is
an attempt therefore to distill those qualities into some lightweight code,
and comes off of the back of two of my earliar attempts: 
[Micro](https://github.com/rawebone/Micro) and 
[Razor](https://github.com/rawebone/Razor). Both of these libraries had good
qualities but both focused on mapping HTTP Methods to handlers; Micro got the
structure right, Razor got service injection. Wilson has both.


## Application in a Nutshell

```php
<?php

require_once "vendor/autoload.php";

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Services allow us to decouple and lazy load our code. Additionally
 * other libraries can wrap themselves up with a provider and expose
 * itself to your application through the framework.
 */
class Services implements \Wilson\Injection\ProviderInterface
{
    public function register(\Wilson\Injection\Injector $injector)
    {
        // Define a connection object here - this can be accessed
        // by the name of "conn" in our function signatures.
        $injector->service("conn", function ()
        {
            return new PDO($dsn, $user, $pass);
        });
    }
}

/**
 * Wilson is organised around the concept of resources which are plain,
 * annotated objects that define how requests should be routed.
 */
class UserResource
{
    /**
     * @route GET /users
     */
    public function collection(PDO $conn, Response $resp)
    {
        $result = $conn->query("SELECT * FROM table");
        
        $resp->headers->set("Content-Type", "application/json");
        $resp->setContent(json_encode($result->fetchAll(), true));
    }
    
    /**
     * @route GET /users/{id}
     */
    public function item(PDO $conn, Response $resp, Request $req)
    {
        $stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");
        $stmt->execute(array($req->get("id"));
        
        $resp->headers->set("Content-Type", "application/json");
        $resp->setContent(json_encode($result->fetchAll(), true));
    }
    
    /**
     * @route GET /users/{id:\d+}/notifications
     */
    public function notifications()
    {
        // ...
    }
}

$config = array(
    // Disables errors being output with the default
    // error handler.
    "environment" => "production",
     
    // This enables the router caching.
    // The router cache will not be refreshed with changes to the app.
    "cachePath" => "/var/www/cache/router.php"
);

$api = \Wilson\Api::createServer();
$api->attach(new UserResource())
    ->service(new Services())
    ->run();

// Et viola ...

```

## TODO

* Improve Test Coverage
* Implement middleware handling


## License

[MIT License](LICENSE), go hog wild.

