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

use Wilson\Http\Request;
use Wilson\Http\Response;
use Wilson\Security\Filter;

/**
 * This is a basic service container that allows for lazy loading of objects.
 * An instance of this object will be passed to the Controller and other
 * handlers in the framework.
 *
 * This object should be extended with getters in the form of getConnection,
 * and the service can be gotten by calling $service->connection.
 *
 * The following services are provided by default:
 *
 * @property Filter $filter
 */
class Services
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string[]
     */
    protected $loaded = array();

    /**
     * Sets the request and the response which can be used by other objects.
     *
     * !!! WARNING !!! calling this method also clears the instance cache.
     *
     * @param Request $request
     * @param Response $response
     */
    public function initialise(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        // Clear down any loaded services
        foreach ($this->loaded as $service) {
            unset($this->$service);
        }

        $this->loaded = array();
    }

    /**
     * Returns a service identified by name.
     *
     * @param string $name
     * @return object
     * @throws \ErrorException
     */
    public function __get($name)
    {
        $factory = "get" . ucfirst($name);
        if (!method_exists($this, $factory)) {
            throw new \ErrorException("Unknown service '$name'");
        }

        $this->loaded[] = $name;

        // Cache this to the Service object itself for a faster,
        // PHP engine based lookup
        return $this->$name = $this->$factory();
    }

    /**
     * Returns a filter object. This can be overridden by the application
     * if you want more specific filtration methods.
     *
     * @return Filter
     */
    protected function getFilter()
    {
        return new Filter();
    }
}
