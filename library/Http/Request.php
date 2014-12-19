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
 * Parts of this code are based off the Slim\Http\Request and Slim\Environment
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

class Request extends MessageAbstract
{
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
    protected $scheme;

    /**
     * @var string
     */
    protected $serverProtocol;

    /**
     * @var string
     */
    protected $content;

    /**
     * This is a merged version of the $_GET and $_POST arrays.
     *
     * @var array
     */
    protected $request = array();

    /**
     * $_FILES superglobal
     *
     * @var array
     */
    protected $files = array();

    public function initialise(array $server, array $get, array $post,
                                array $cookies, array $files, $input = "php://input")
    {
		$this->request = array_merge($get, $post);
        $this->files   = $files;

        $this->method = $server["REQUEST_METHOD"];
        $this->ip = $server["REMOTE_ADDR"];
        $this->serverName = $server["SERVER_NAME"];
        $this->serverPort = $server["SERVER_PORT"];
        $this->serverProtocol = $server["SERVER_PROTOCOL"];

        // Server params
        $scriptName  = $server["SCRIPT_NAME"]; // <-- "/foo/index.php"
        $requestUri  = $server["REQUEST_URI"]; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
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

        $this->scheme = empty($server["HTTPS"]) || $server["HTTPS"] === "off" ? "http" : "https";

        // Input stream (readable one time only; not available for multipart/form-data requests)
        $content = @file_get_contents($input);
        if (!$content) {
            $content = "";
        }
        $this->content = $content;

        $this->setHeaders($server);

        // Method Override
        if ($this->hasHeader("HTTP_X_HTTP_METHOD_OVERRIDE")) {
            $this->originalMethod = $this->method;
            $this->method = strtoupper($this->getHeader("HTTP_X_HTTP_METHOD_OVERRIDE"));
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
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getHeader("PHP_AUTH_USER");
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getHeader("PHP_AUTH_PW");
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->getHeader("HTTP_X_REQUESTED_WITH", "") === "XMLHttpRequest";
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return $this->getProtocol() === "https";
    }

    /**
     * @return bool
     */
    public function isSafeMethod()
    {
        return $this->method === "HEAD" || $this->method === "GET";
    }

    /**
     * @param string $expr
     * @return bool
     */
    public function isUserAgentLike($expr)
    {
        return preg_match("/$expr/i", $this->getUserAgent()) === 1;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->getHeader("HTTP_USER_AGENT", "");
    }

	/**
	 * Returns a request parameter, or the default.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParam($key, $default = null)
	{
		return isset($this->request[$key]) ? $this->request[$key] : $default;
	}

	/**
	 * Sets a request parameter, if the value is null then the parameter will
	 * be unset.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setParam($key, $value)
	{
		$this->request[$key] = $value;
	}

    /**
     * @param string $key
     * @return void
     */
    public function unsetParam($key)
    {
        unset($this->request[$key]);
    }

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->request;
	}

	/**
	 * @param array $params
	 * @return void
	 * @see setParam
	 */
	public function setParams(array $params)
	{
		foreach ($params as $key => $value) {
			$this->setParam($key, $value);
		}
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
     * Returns an array of ETags sent with the Request.
     *
     * @see Symfony\Component\HttpFoundation\Request::getETags
     * @return array
     */
    public function getETags()
    {
        return preg_split('/\s*,\s*/', $this->getHeader("HTTP_IF_NONE_MATCH"), null, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return string|null
     */
    public function getModifiedSince()
    {
        return $this->getHeader("HTTP_IF_MODIFIED_SINCE");
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->getHeader("HTTP_CONTENT_TYPE");
    }

    /**
     * Get Media Type (type/subtype within Content Type header)
     * @return string|null
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split("/\\s*[;,]\\s*/", $contentType);

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
            $contentTypeParts = preg_split("/\\s*[;,]\\s*/", $contentType);
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
        return $this->getHeader("HTTP_CONTENT_LENGTH", 0);
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->serverProtocol;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        if ($this->hasHeader("HTTP_HOST")) {
            $host = $this->getHeader("HTTP_HOST");
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
    public function getScheme()
    {
        return $this->scheme;
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
        $url = $this->getScheme() . "://" . $this->getHost();
        if (($this->getScheme() === "https" && $this->getPort() !== 443) || ($this->getScheme() === "http" && $this->getPort() !== 80)) {
            $url .= sprintf(":%s", $this->getPort());
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        if ($this->hasHeader("HTTP_X_FORWARDED_FOR")) {
            return $this->getHeader("HTTP_X_FORWARDED_FOR");

        } else if ($this->hasHeader("HTTP_CLIENT_IP")) {
            return $this->getHeader("HTTP_CLIENT_IP");

        } else {
            return $this->ip;
        }
    }
}