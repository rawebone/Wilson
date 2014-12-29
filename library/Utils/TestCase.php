<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Utils;

use Wilson\Http\Request;
use Wilson\Http\Response;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Wilson\Api
     */
    protected $_api;

    protected function setUp()
    {
        $this->_api = $this->getApi();
        $this->_api->testing = true;
    }

    /**
     * Makes a request via the framework.
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $get
     * @param array $post
     * @param string $content
     * @return \Wilson\Http\Response
     */
    protected function call($method, $uri, $headers = array(), $get = array(), $post = array(), $content = "")
    {
        $server = array_merge($headers, array(
            "REQUEST_METHOD" => $method,
            "REQUEST_URI" => $uri
        ));

        $response = new Response();
        $request  = new Request();
        $request->mock($server, $get, $post, array(), array(), $content);

        $this->_api->dispatch($request, $response);

        return $response;
    }

    /**
     * Override this method to give the framework your API instance.
     *
     * @return \Wilson\Api
     */
    protected abstract function getApi();
}