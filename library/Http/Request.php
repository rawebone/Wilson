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
 * Represents an HTTP Request Message.
 *
 * This objects is derived from code taken from the Slim and Symfony frameworks.
 * Their licences are included with this project and links are provided below:
 *
 * @link https://github.com/rawebone/Wilson/LICENSE.SLIM
 * @link https://github.com/rawebone/Wilson/LICENSE.SYMFONY
 */
class Request extends MessageAbstract
{
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

    public function mock($server = array(), $get = array(), $post = array(), $cookies = array(), $files = array(), $content = "")
    {
        $defaults = array(
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/",
            "SCRIPT_NAME" => "/index.php",
            "PATH_INFO" => "",
            "QUERY_STRING" => "",
            "SERVER_NAME" => "localhost",
            "SERVER_PORT" => 80,
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "ACCEPT" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "ACCEPT_LANGUAGE" => "en-US,en;q=0.8",
            "ACCEPT_CHARSET" => "ISO-8859-1,utf-8;q=0.7,*;q=0.3",
            "USER_AGENT" => "Wilson Framework",
            "REMOTE_ADDR" => "127.0.0.1",
            "HTTPS" => "off"
        );

        $this->initialise(array_merge($defaults, $server), $get, $post, $cookies, $files, $content);
    }

    public function initialise(
        array $server,
        array $get,
        array $post,
        array $cookies,
        array $files,
        $content = ""
    ) {
        $this->content = $content;
        $this->files = $files;

        $this->buildCookies($cookies);
        $this->buildServerInfo($server);
        $this->buildPaths($server);
        $this->setAllHeaders($server);
        $this->setAllParams(array_merge($get, $post));

        // Method Override
        if (($original = $this->getHeader("HTTP_X_HTTP_METHOD_OVERRIDE"))) {
            $this->originalMethod = $this->method;
            $this->method = strtoupper($original);
        }
    }

    /**
     * @param array $cookies
     */
    protected function buildCookies(array $cookies)
    {
        foreach ($cookies as $name => $value) {
            $this->addCookie(new Cookie($name, $value, null, null, null, false, false));
        }
    }

    /**
     * @param array $server
     */
    protected function buildServerInfo(array $server)
    {
        $this->method = $server["REQUEST_METHOD"];
        $this->ip = $server["REMOTE_ADDR"];
        $this->serverName = $server["SERVER_NAME"];
        $this->serverPort = $server["SERVER_PORT"];
        $this->serverProtocol = $server["SERVER_PROTOCOL"];
        $this->scheme = empty($server["HTTPS"]) || $server["HTTPS"] === "off" ? "http" : "https";
    }

    /**
     * Builds up the physical and virtual paths.
     *
     * @param array $server
     */
    protected function buildPaths(array $server)
    {
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
    }

    /**
     * Returns the body sent with the request.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the username associated with the request ($_SERVER["PHP_AUTH_USER"]).
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getHeader("PHP_AUTH_USER");
    }

    /**
     * Returns the password associated with the request ($_SERVER["PHP_AUTH_PW"]).
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getHeader("PHP_AUTH_PW");
    }

    /**
     * Returns whether the request was made via XMLHttpRequest.
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getHeader("HTTP_X_REQUESTED_WITH", "") === "XMLHttpRequest";
    }

    /**
     * Returns whether the communications channel is secured by SSL.
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->getScheme() === "https";
    }

    /**
     * Returns the HTTP protocol version the request was made with.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->serverProtocol;
    }

    /**
     * Returns whether the request method is considered idempotent.
     *
     * @return bool
     */
    public function isSafeMethod()
    {
        return $this->method === "HEAD" || $this->method === "GET";
    }

    /**
     * Returns whether the User Agent matches the passed regular expression.
     *
     * @param string $expr
     * @return bool
     */
    public function isUserAgentLike($expr)
    {
        return preg_match($expr, $this->getUserAgent()) === 1;
    }

    /**
     * Returns the User Agent sent with the request.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->getHeader("HTTP_USER_AGENT", "");
    }

    /**
     * Returns any files associated with the request. This is a copy of
     * the $_FILES superglobal.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Returns whether the content body contains encoded values.
     *
     * @return bool
     */
    public function isFormData()
    {
        $method = $this->getOriginalMethod() ?: $this->getMethod();

        return ($method === "POST" && is_null($this->getContentType())) || $this->getContentMimeType() ===
            "application/x-www-form-urlencoded";
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
     * Returns the HTTP method used to make the request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the MIME type of the content sent with the request, if any.
     *
     * @return string|null
     */
    public function getContentType()
    {
        return $this->getHeader("HTTP_CONTENT_TYPE");
    }

    /**
     * Returns the MIME type of the
     *
     * @return string|null
     */
    public function getContentMimeType()
    {
        if (($contentType = $this->getContentType())) {
            $contentTypeParts = preg_split("/\\s*[;,]\\s*/", $contentType);
            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Returns an array containing the parameters associated with the content,
     * if any.
     *
     * @return array
     */
    public function getContentMimeTypeParameters()
    {
        $params = array();
        if (($contentType = $this->getContentType())) {
            $parts = preg_split("/\\s*[;,]\\s*/", $contentType);

            for ($i = 1, $length = count($parts); $i < $length; $i++) {
                $split = explode("=", $parts[$i]);
                $params[strtolower($split[0])] = $split[1];
            }
        }

        return $params;
    }

    /**
     * Returns the charset used to encode the content sent with the request,
     * if any.
     *
     * @return string|null
     */
    public function getContentCharset()
    {
        $params = $this->getContentMimeTypeParameters();
        if (isset($params["charset"])) {
            return $params["charset"];
        }

        return null;
    }

    /**
     * Returns the length of the content sent with the request, if any.
     *
     * @return int
     */
    public function getContentLength()
    {
        return $this->getHeader("HTTP_CONTENT_LENGTH", 0);
    }

    /**
     * Returns an array of ETags sent with the Request.
     *
     * @see Symfony\Component\HttpFoundation\Request::getETags()
     * @return array
     */
    public function getETags()
    {
        return preg_split("/\\s*,\\s*/", $this->getHeader("HTTP_IF_NONE_MATCH"), null, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns a GMT formatted time string with the last known modification time
     * of a resource, if any.
     *
     * @return string|null
     */
    public function getModifiedSince()
    {
        return $this->getHeader("HTTP_IF_MODIFIED_SINCE");
    }

    /**
     * @return string
     */
    public function getHostWithPort()
    {
        return sprintf("%s:%s", $this->getHost(), $this->getPort());
    }

    /**
     * Returns the hostname of the current server
     *
     * @return string
     */
    public function getHost()
    {
        if (($host = $this->getHeader("HTTP_HOST"))) {
            $pos = strpos($host, ":");
            return ($pos !== false ? substr($host, 0, $pos) : $host);
        }

        return $this->serverName;
    }

    /**
     * Returns the port on the current server which received the request.
     *
     * @return int
     */
    public function getPort()
    {
        return (int)$this->serverPort;
    }

    /**
     * Get Path (physical path + virtual path)
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getPhysicalPath() . $this->getPathInfo();
    }

    /**
     * @return string
     */
    public function getPhysicalPath()
    {
        return $this->physicalPath;
    }

    /**
     * @return string
     */
    public function getPathInfo()
    {
        return $this->pathInfo;
    }

    /**
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * Get URL (scheme + host [ + port if non-standard ])
     *
     * @return string
     */
    public function getUrl()
    {
        $scheme = $this->getScheme();
        $port   = $this->getPort();
        $ports  = array("http"  => 80, "https" => 443);

        return $scheme . "://" . $this->getHost() . ($ports[$scheme] !== $port ? ":$port" : "");
    }

    /**
     * Returns the URL protocol schema used to make the request.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        if (($forwarded = $this->getHeader("HTTP_X_FORWARDED_FOR"))) {
            return $forwarded;

        } else if (($client = $this->getHeader("HTTP_CLIENT_IP"))) {
            return $client;

        } else {
            return $this->ip;
        }
    }
}