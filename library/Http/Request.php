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

class Request extends MessageAbstract
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * @var array
     */
    protected static $formDataMediaTypes = array("application/x-www-form-urlencoded");

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string|null
     */
    protected $originalMethod;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $physicalPath;

    /**
     * @var string
     */
    protected $pathInfo;

    /**
     * @var string
     */
    protected $queryString;

    /**
     * @var string
     */
    protected $serverName;

    /**
     * @var int
     */
    protected $serverPort;

    /**
     * @var string
     */
    protected $protocol;

    /**
     * @var string
     */
    protected $content;

    /**
     * $_GET superglobal
     *
     * @var array
     */
    protected $get = array();

    /**
     * $_POST superglobal
     *
     * @var array
     */
    protected $post = array();

    /**
     * $_FILES superglobal
     *
     * @var array
     */
    protected $files = array();

    public function __construct(array $server, array $get, array $post,
                                array $cookies, array $files, $input = "php://input")
    {
        $this->get = $get;
        $this->post = $post;
        $this->files = $files;

        $this->method = $server["REQUEST_METHOD"];
        $this->ip = $server["REMOTE_ADDR"];
        $this->serverName = $server["SERVER_NAME"];
        $this->serverPort = $server["SERVER_PORT"];

        // Server params
        $scriptName = $server["SCRIPT_NAME"]; // <-- "/foo/index.php"
        $requestUri = $server["REQUEST_URI"]; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
        $queryString = isset($server["QUERY_STRING"]) ? $server["QUERY_STRING"] : ""; // <-- "test=abc" or ""

        // Physical path
        if (strpos($requestUri, $scriptName) !== false) {
            $physicalPath = $scriptName; // <-- Without rewriting
        } else {
            $physicalPath = str_replace("\\", "", dirname($scriptName)); // <-- With rewriting
        }
        $this->physicalPath = rtrim($physicalPath, "/"); // <-- Remove trailing slashes

        // Virtual path
        $path = substr_replace($requestUri, "", 0, strlen($physicalPath)); // <-- Remove physical path
        $path = str_replace("?" . $queryString, "", $path); // <-- Remove query string
        $this->pathInfo = "/" . ltrim($path, "/"); // <-- Ensure leading slash

        // Query string (without leading "?")
        $this->queryString = $queryString;

        $this->protocol = empty($server["HTTPS"]) || $server["HTTPS"] === "off" ? "http" : "https";

        // Input stream (readable one time only; not available for multipart/form-data requests)
        $content = @file_get_contents($input);
        if (!$content) {
            $content = "";
        }
        $this->content = $content;

        // Process Headers into real form (HTTP_CONTENT_TYPE => Content-Type)
        foreach ($server as $key => $value) {
            if (strpos($key, "HTTP_COOKIE") === 0) {
                // Cookies are handled using the $cookie parameter
                continue;
            }

            if ($value && strpos($key, "HTTP_") === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');

                $this->setHeader($name, $value);
                continue;
            }

            if ($value && strpos($key, "CONTENT_") === 0) {
                $name = substr($key, 8); // Content-
                $name = "Content-" . (($name == "MD5") ? $name : ucfirst(strtolower($name)));

                $this->setHeader($name, $value);
                continue;
            }
        }

        // Method Override
        if ($this->hasHeader("X-Http-Method-Override")) {
            $this->originalMethod = $this->method;
            $this->method = strtoupper($this->getHeader("X-Http-Method-Override"));
        }

        // Cookies
        foreach ($cookies as $cookie) {

        }
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns a string if a method override has been used.
     *
     * @return null|string
     */
    public function getOriginalMethod()
    {
        return $this->originalMethod;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->getHeader("X-Requested-With", "") === "XMLHttpRequest";
    }

    /**
     * Returns a parameter from the GET and POST data associated with the request.
     * This is less performant that get() or post(), and GET data is prioritised.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        if (($get = $this->get($key))) {
            return $get;
        }

        if (($post = $this->post($key))) {
            return $post;
        }

        return $default;
    }

    /**
     * Returns an entry from the GET data or a default value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }

    /**
     * Returns an entry from the POST data or a default value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post($key, $default = null)
    {
        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    /**
     * Returns any files associated with the request. This is a copy of
     * the $_FILES superglobal.
     *
     * @return array
     */
    public function files()
    {
        return $this->files;
    }

    /**
     * @return bool
     */
    public function isFormData()
    {
        $method = $this->getOriginalMethod() ?: $this->getMethod();

        return ($method === "POST" && is_null($this->getContentType())) || in_array($this->getMediaType(), self::$formDataMediaTypes);
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->getHeader("Content-Type");
    }

    /**
     * Get Media Type (type/subtype within Content Type header)
     * @return string|null
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split("/\s*[;,]\s*/", $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getMediaTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = array();
        if ($contentType) {
            $contentTypeParts = preg_split("/\s*[;,]\s*/", $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode("=", $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * @return string|null
     */
    public function getContentCharset()
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams["charset"])) {
            return $mediaTypeParams["charset"];
        }

        return null;
    }

    /**
     * @return int
     */
    public function getContentLength()
    {
        return $this->getHeader("Content-Length", 0);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        if ($this->hasHeader("Host")) {
            $host = $this->getHeader("Host");
            if (strpos($host, ":") !== false) {
                $hostParts = explode(":", $host);
                $host = $hostParts[0];
            }

            return $host;
        }

        return $this->serverName;
    }

    /**
     * @return string
     */
    public function getHostWithPort()
    {
        return sprintf("%s:%s", $this->getHost(), $this->getPort());
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return (int)$this->serverPort;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getPhysicalPath()
    {
        return $this->physicalPath;
    }

    /**
     * Get Path (physical path + virtual path)
     * @return string
     */
    public function getPath()
    {
        return $this->getPhysicalPath() . $this->getPathInfo();
    }

    /**
     * @return string
     */
    public function getPathInfo()
    {
        return $this->pathInfo;
    }

    /**
     * Get URL (scheme + host [ + port if non-standard ])
     * @return string
     */
    public function getUrl()
    {
        $url = $this->getProtocol() . "://" . $this->getHost();
        if (($this->getProtocol() === "https" && $this->getPort() !== 443) || ($this->getProtocol() === "http" && $this->getPort() !== 80)) {
            $url .= sprintf(":%s", $this->getPort());
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        if ($this->hasHeader("X-Forwarded-For")) {
            return $this->getHeader("X-Forwarded-For");

        } else if ($this->hasHeader("Client-Ip")) {
            return $this->getHeader("Client-Ip");

        } else {
            return $this->ip;
        }
    }
}