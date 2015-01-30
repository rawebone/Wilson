<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Http;

    /**
     * Parts of this code are based off the Slim\Http\Header and Slim\Helpers\Set
     * objects included in the Slim framework.
     *
     * Slim - a micro PHP 5 framework
     *
     * @author      Josh Lockhart <info@slimframework.com>
     * @copyright   2011 Josh Lockhart
     * @link        http://www.slimframework.com
     * @license     http://www.slimframework.com/license
     * @version     2.4.2
     * @package     Slim
     *
     * MIT LICENSE
     *
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     *
     * The above copyright notice and this permission notice shall be
     * included in all copies or substantial portions of the Software.
     *
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
     * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
     * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
     * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */

/**
 * MessageAbstract provides the basic handling for HTTP Request/Responses.
 *
 * This consolidates the Cookie and Header handling from the Slim frameworks
 * HTTP layer into a single package.
 */
abstract class MessageAbstract
{
    /**
     * @var string|callable
     */
    private $body = "";

    /**
     * @var Cookie[]
     */
    private $cookies = array();

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $params = array();

    /**
     * Adds a Cookie object to the message
     *
     * @param Cookie $cookie
     * @return void
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->name] = $cookie;
    }

    /**
     * Returns the body of the message.
     *
     * @return string|callable
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns a cookie associated with the message by name.
     *
     * @return Cookie|null
     */
    public function getCookie($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }

    /**
     * Returns any cookies associated with the message.
     *
     * @return Cookie[]
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Returns the value of a header by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : $default;
    }

    /**
     * Returns all headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns whether a header has been set.
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Returns whether a parameter has been set.
     *
     * @param string $name
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Returns a request parameter, or the default.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * Returns all defined parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets all headers, overwriting any already set.
     *
     * @param array $headers
     * @return void
     */
    public function setAllHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Sets all parameters, overwriting any previously set.
     *
     * @param array $params
     * @return void
     */
    public function setAllParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Sets the body of the message.
     *
     * @param string|callable $content
     * @return void
     */
    public function setBody($content)
    {
        if (!is_callable($content) && !is_string($content)) {
            throw new \InvalidArgumentException("Message body is expected to be a string or a callable");
        }

        $this->body = $content;
    }

    /**
     * Sets a UTC formatted date string from the given datetime object.
     *
     * @param string $name
     * @param \DateTime $date
     * @return void
     */
    public function setDateHeader($name, \DateTime $date)
    {
        $date = clone $date;
        $date->setTimezone(new \DateTimeZone("UTC"));

        $this->headers[$name] = $date->format("D, d M Y H:i:s T");
    }

    /**
     * Sets a header by name.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sets headers, adding to the headers already set.
     *
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Sets a message parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Sets message parameters, appending to those already set.
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params)
    {
        foreach ($params as $name => $value) {
            $this->params[$name] = $value;
        }
    }

    /**
     * Clears a header by name.
     *
     * @param string $name
     * @return void
     */
    public function unsetHeader($name)
    {
        unset($this->headers[$name]);
    }

    /**
     * Clears headers by name.
     *
     * @param array $names
     * @return void
     */
    public function unsetHeaders(array $names)
    {
        foreach ($names as $name) {
            unset($this->headers[$name]);
        }
    }

    /**
     * Removes a parameter from the message by name.
     *
     * @param string $name
     * @return void
     */
    public function unsetParam($name)
    {
        unset($this->params[$name]);
    }
}