<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson;

use Exception;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Http\Sender;
use Wilson\Routing\Dispatcher;
use Wilson\Routing\Router;
use Wilson\Routing\UrlTools;
use Wilson\Security\Filter;
use Wilson\Security\RequestValidation;
use Wilson\Utils\Cache;

class Api
{
    /**
     * Defines the file we should use to cache framework data.
     *
     * @see createCache
     * @var string
     */
    public $cacheFile;

    /**
     * Defines a callable which will be used to handle any errors during dispatch.
     * The signature of the callable is:
     *
     * <code>
     * function (Request $request, Response $response, Services $services, Exception $exception) {}
     * </code>
     *
     * @see defaultError
     * @var callable
     */
    public $error;

    /**
     * Defines a callable which will be used when a request cannot be matched
     * to a route. The signature of the callable is:
     *
     * <code>
     * function (Request $request, Response $response, Services $services) {}
     * </code>
     *
     * @see defaultNotFound
     * @var callable
     */
    public $notFound;

    /**
     * This handler is used to prepare a request and response during dispatch.
     * The signature for this callable is:
     *
     * <code>
     * function (Request $request, Response $response, Services $services) {}
     * </code>
     *
     * Exceptions thrown by this handler will trigger the error handler.
     *
     * @see error
     * @var callable
     */
    public $prepare;

    /**
     * Holds all of the object names that define your API. These should be
     * defined as:
     *
     * <code>
     * $api->resources = array(
     *     "My\Restful\ResourceA",
     *       "My\Restful\ResourceB"
     * ):
     * </code>
     *
     * When the framework matches a request an instance of the object will be
     * created automatically to fulfill the dispatch; this allows us to put off
     * creating objects until absolutely necessary. However this does mean that
     * the resource objects constructors cannot have any non-defaulted parameters.
     * If your object has dependencies you should define them in a Service
     * container.
     *
     * @see services
     * @var string[]
     */
    public $resources = array();

    /**
     * The Service container is passed to every Controller in your application.
     * A default is provided for you without any registered services.
     *
     * @see Services
     * @var Services
     */
    public $services;

    /**
     * Flags that the application is being unit tested. This prevents the Api
     * from sending responses back to the User Agent.
     *
     * @var bool
     */
    public $testing = false;

    public function __construct()
    {
        $this->services = new Services();
        $this->prepare = array($this, "defaultPrepare");
        $this->error = array($this, "defaultError");
        $this->notFound = array($this, "defaultNotFound");
    }

    /**
     * This method can be used to give a significant performance boost to your
     * application by pre-generating and caching the routing table. Note that
     * this caching is NOT performed automatically and that the $api->cacheFile
     * property needs to be set to a writable path for this to succeed.
     *
     * @see cacheFile
     * @return void
     */
    public function createCache()
    {
        $cache = new Cache($this->cacheFile);
        $router = new Router($cache, new UrlTools());

        $table = $router->getRoutingTable($this->resources);
        $cache->set("router", $table);
    }

    /**
     * Provides a minimal default error response, emitting a HTTP Status 500
     * and writing the exception to the User Agent.
     *
     * @param Request $request
     * @param Response $response
     * @param Services $services
     * @param Exception $exception
     * @return void
     */
    public function defaultError(Request $request, Response $response, Services $services, Exception $exception)
    {
        $response->html("<pre>$exception</pre>", 500);
    }

    /**
     * Provides a minimal default not found response, emitting a HTTP Status 404
     * and writing "Not Found" to the User Agent.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function defaultNotFound(Request $request, Response $response)
    {
        $response->html("<b>Not Found</b>", 404);
    }

    /**
     * Placeholder prepare function.
     *
     * @return void
     */
    public function defaultPrepare()
    {

    }

    /**
     * Dispatches the request and, if the Api is not under test, sends the
     * response back to the User Agent.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function dispatch(Request $request = null, Response $response = null)
    {
        if (!$request) {
            $request = new Request();
            $request->initialise($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, file_get_contents("php://input"));
        }

        if (!$response) {
            $response = new Response();
        }

        $this->services->initialise($request, $response);

        $router = new Router(new Cache($this->cacheFile), new UrlTools());
        $sender = new Sender($request, $response);
        $validation = new RequestValidation($this->services->filter, $request);

        $dispatcher = new Dispatcher($this, $request, $response, $router, $sender, $validation);
        $dispatcher->dispatch();
    }
}
