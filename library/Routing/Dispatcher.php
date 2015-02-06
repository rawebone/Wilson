<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Routing;

use Exception;
use Wilson\Api;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Http\Sender;
use Wilson\Security\RequestValidation;

/**
 * Dispatcher is responsible for routing requests and sending responses.
 */
class Dispatcher
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var RequestValidation
     */
    protected $validation;

    public function __construct(Api $api, Request $request, Response $response,
        Router $router, Sender $sender, RequestValidation $validation)
    {
        $this->api = $api;
        $this->request = $request;
        $this->response = $response;
        $this->router = $router;
        $this->sender = $sender;
        $this->validation = $validation;
    }

    /**
     * Dispatches the request and sends the response to the client if the
     * system is not being tested.
     *
     * @return
     */
    public function dispatch()
    {
        try {
            $this->applyDefaultSecurity();

            // Call the User Prepare function
            $this->dispatchController($this->api->prepare);
            $this->routeRequest();

        } catch (\Exception $exception) {
            $this->dispatchController($this->api->error, $exception);
        }

        if (!$this->api->testing) {
            $this->sender->send();
        }
    }

    /**
     * Sets Headers on the response to help prevent XSS, Click-Jacking, etc.
     *
     * These can be overridden by the $api->prepare handler, if required.
     *
     * It is recommended that HSTS also be turned on, but that is potentially
     * handled better by the Web Server than the Web Application.
     *
     * @link http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
     * @link https://www.veracode.com/blog/2014/03/guidelines-for-setting-security-headers/
     * @link https://www.owasp.org/index.php/HTML5_Security_Cheat_Sheet#HTTP_Headers_to_enhance_security
     *
     * @return void
     */
    protected function applyDefaultSecurity()
    {
        $this->response->setHeaders(array(
            "X-Content-Type-Options" => "no-sniff",
            "X-Frame-Options" => "deny",
            "X-XSS-Protection" => "1"
        ));
    }

    /**
     * Routes the request the handler most appropriate.
     *
     * @return void
     */
    protected function routeRequest()
    {
        $match = $this->router->match(
            $this->api->resources,
            $this->request->getMethod(),
            $this->request->getPathInfo()
        );

        switch ($match->status) {
            case Router::FOUND:
                $this->routeToHandlers($match);
                break;

            case Router::NOT_FOUND:
                $this->routeToNotFound();
                break;

            case Router::METHOD_NOT_ALLOWED:
                $this->routeToNotAllowed($match);
                break;
        }
    }

    /**
     * Calls the handlers of a match in turn until the stack is exhausted or a
     * handler returns false.
     *
     * @param \stdClass $match
     * @return void
     */
    protected function routeToHandlers($match)
    {
        $this->request->setParams($match->params);

        $this->validation->validate($match->handlers);

        // Dispatch all middleware, abort if they return boolean false
        foreach ($match->handlers as $handler) {
            if ($this->dispatchController($handler) === false) {
                return;
            }
        }
    }

    /**
     * Route the request to the defined Not Found handler.
     *
     * @return void
     */
    protected function routeToNotFound()
    {
        $this->dispatchController($this->api->notFound);
    }

    /**
     * Responds to the client to confirm method not allowed.
     *
     * @param \stdClass $match
     * @return void
     */
    protected function routeToNotAllowed($match)
    {
        $status = $this->request->getMethod() === "OPTIONS" ? 200 : 405;

        $this->response->setHeader("Allow", join(", ", $match->allowed));
        $this->response->setStatus($status);
    }

    /**
     * Invokes a callable with the required arguments for a controller.
     *
     * @param callable $controller
     * @param Exception $exception
     * @return boolean|void
     */
    protected function dispatchController($controller, Exception $exception = null)
    {
        return call_user_func(
            $controller,
            $this->request,
            $this->response,
            $this->api->services,
            $exception
        );
    }
}
