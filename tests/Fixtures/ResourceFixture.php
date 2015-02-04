<?php

namespace Wilson\Tests\Fixtures;

use Wilson\Http\Request;
use Wilson\Http\Response;

class ResourceFixture
{
    /**
     * @route GET /route-1
     */
    function route1(Request $request, Response $response)
    {
        $response->setBody("found");
    }

    /**
     * @route GET /route-2
     * @through fail
     */
    function route2(Request $request, Response $response)
    {
        $response->setBody("Oops, this isn't right");
    }

    /**
     * @route GET /route-3
     */
    function route3()
    {
        throw new \ErrorException("blah");
    }

    /**
     * @route GET /route-4
     * @through middleware
     */
    function route4(Request $request, Response $response)
    {
        $response->setBody("found");
    }

    function middleware(Request $request)
    {
        $request->setParam("middleware_called", true);
    }

    function fail(Request $request, Response $response)
    {
        $response->setBody("failed");
        return false;
    }
}
