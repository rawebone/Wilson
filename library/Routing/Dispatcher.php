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

use Wilson\Api;
use Wilson\Services;
use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Http\Sender;

/**
 * Dispatcher is responsible for routing requests and sending responses.
 */
class Dispatcher
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Sender
     */
    protected $sender;

    public function __construct(Router $router, Sender $sender)
    {
        $this->router = $router;
        $this->sender = $sender;
    }

    /**
     * Dispatches the request and sends the response to the client, if the
     * system is not being tested.
     *
     * @param Api $api
     * @param Request $request
     * @param Response $response
     */
    public function dispatch(Api $api, Request $request, Response $response)
    {
        try {
            call_user_func($api->prepare, $request, $response);
            $this->routeRequest($api, $request, $response);

        } catch (\Exception $exception) {
            call_user_func($api->error, $request, $response, $api->services, $exception);
        }

        if (!$api->testing) {
            $this->sender->send($request, $response);
        }
    }

    /**
     * Routes the request the handler most appropriate.
     *
     * @param Api $api
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function routeRequest(Api $api, Request $request, Response $response)
    {
        $match = $this->router->match(
            $api->resources,
            $request->getMethod(),
            $request->getPathInfo()
        );

        switch ($match->status) {
            case Router::FOUND:
                $this->routeToHandlers($match, $request, $response, $api->services);
                break;

            case Router::NOT_FOUND:
                call_user_func($api->notFound, $request, $response, $api->services);
                break;

            case Router::METHOD_NOT_ALLOWED:
                $response->setHeader("Allow", join(", ", $match->allowed));
                $response->setStatus($request->getMethod() === "OPTIONS" ? 200 : 405);
                break;
        }
    }

    /**
     * Calls the handlers of a match in turn until the stack is exhausted or a
     * handler returns false.
     *
     * @param object $match
     * @param Request $request
     * @param Response $response
     * @param Services $services
     * @return void
     */
    protected function routeToHandlers($match, Request $request, Response $response, Services $services)
    {
        $request->setParams($match->params);

        // Dispatch all middleware, abort if they return boolean false
        foreach ($match->handlers as $handler) {

            $result = call_user_func(
                $handler,
                $request,
                $response,
                $services
            );

            if ($result === false) {
                return;
            }
        }
    }
}