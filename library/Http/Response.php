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
 * This object has been derived from Slim framework in the main and Symfony
 * HttpFoundation in other places. Their licences:
 */

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

/**
 * Copyright (c) 2004-2014 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Response extends MessageAbstract
{
    /**
     * @var array HTTP response codes and messages
     */
    protected static $messages = array(
        // Informational 1xx
        100 => "100 Continue",
        101 => "101 Switching Protocols",

        // Successful 2xx
        200 => "200 OK",
        201 => "201 Created",
        202 => "202 Accepted",
        203 => "203 Non-Authoritative Information",
        204 => "204 No Content",
        205 => "205 Reset Content",
        206 => "206 Partial Content",

        // Redirection 3xx
        300 => "300 Multiple Choices",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        304 => "304 Not Modified",
        305 => "305 Use Proxy",
        306 => "306 (Unused)",
        307 => "307 Temporary Redirect",

        // Client Error 4xx
        400 => "400 Bad Request",
        401 => "401 Unauthorized",
        402 => "402 Payment Required",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        406 => "406 Not Acceptable",
        407 => "407 Proxy Authentication Required",
        408 => "408 Request Timeout",
        409 => "409 Conflict",
        410 => "410 Gone",
        411 => "411 Length Required",
        412 => "412 Precondition Failed",
        413 => "413 Request Entity Too Large",
        414 => "414 Request-URI Too Long",
        415 => "415 Unsupported Media Type",
        416 => "416 Requested Range Not Satisfiable",
        417 => "417 Expectation Failed",
        418 => "418 I\"m a teapot",
        422 => "422 Unprocessable Entity",
        423 => "423 Locked",

        // Server Error 5xx
        500 => "500 Internal Server Error",
        501 => "501 Not Implemented",
        502 => "502 Bad Gateway",
        503 => "503 Service Unavailable",
        504 => "504 Gateway Timeout",
        505 => "505 HTTP Version Not Supported"
    );
    /**
     * @var string
     */
    protected $protocol = "HTTP/1.1";

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * @return bool
     */
    public function isOk()
    {
        return $this->status === 200;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * @return bool
     */
    public function isRedirection()
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * @return bool
     */
    public function isClientError()
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * @return bool
     */
    public function isServerError()
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * @param string $url
     * @param int $status
     * @return void
     */
    public function setRedirect($url, $status)
    {
        $this->setStatus($status);
        $this->setHeader("Location", $url);
    }

    /**
     * @param \DateTime $date
     * @return void
     */
    public function setExpires(\DateTime $date = null)
    {
        if ($date === null) {
            $this->unsetHeader("Expires");

        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone("UTC"));
            $this->setHeader("Expires", $date->format("D, d M Y H:i:s T"));
        }
    }

    /**
     * @param \DateTime $date
     * @return void
     */
    public function setLastModified(\DateTime $date = null)
    {
        if ($date === null) {
            $this->unsetHeader("Last-Modified");

        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone("UTC"));
            $this->setHeaders("Last-Modified", $date->format("D, d M Y H:i:s T"));
        }
    }

    /**
     * @param string|null $value
     * @param bool $weak
     * @return void
     */
    public function setETag($value = null, $weak = false)
    {
        if ($value === null) {
            $this->unsetHeader("ETag");

        } else {
            $tag = ($weak ? "W/" : "") . "\"$value\"";
            $this->setHeader("ETag", $tag);
        }
    }

    /**
     * Prepares the response for being sent back to the client.
     * This is based off Symfony's HttpFoundation Response::prepare()
     * method and Slim's Response sending.
     *
     * @param Request $request
     * @return void
     */
    public function prepare(Request $request)
    {
        $this->checkForModifications($request);

        // Fix output content
        if ($this->isInformational() || $this->status === 204 || $this->status === 304) {
            $this->setBody("");
            $this->unsetHeader("Content-Type");
            $this->unsetHeader("Content-Length");

        } else {
            $body = $this->getBody();
            if (is_string($body)) {
                $this->setHeader("Content-Length", strlen($body));
            }

            if ($request->getMethod() === "HEAD") {
                $this->setBody("");
            }
        }

        // Match request protocol
        if ($this->protocol !== $request->getProtocol()) {
            $this->protocol = $request->getProtocol();
        }

        // On the older HTTP protocol we need to send more cache headers
        if ($this->protocol === "HTTP/1.0" && $this->getHeader("Cache-Control") === "no-cache") {
            $this->setHeader("Pragma", "no-cache");
            $this->setHeader("Expires", -1);
        }
    }

    /**
     * Determines if the requested resource has been modified since the
     * last request, allowing us to optimise the response. This is based
     * off of Symfony\Component\HttpFoundation\Response::isNotModified().
     *
     * @param Request $request
     */
    public function checkForModifications(Request $request)
    {
        if (!$request->isSafeMethod()) {
            return;
        }

        $notModified = false;
        $lastModified = $this->getHeader("Last-Modified");
        $modifiedSince = $request->getModifiedSince();

        if (($eTags = $request->getETags())) {
            $notModified = in_array($this->getHeader("ETag"), $eTags) || in_array("*", $eTags);
        }

        if ($modifiedSince && $lastModified) {
            $notModified = strtotime($modifiedSince) >= strtotime($lastModified) && (!$eTags || $notModified);
        }

        if ($notModified) {
            $this->setStatus(304);

            // These headers are not allowed to be included with a 304 response
            $headers = array(
                "Allow",
                "Content-Encoding",
                "Content-Language",
                "Content-Length",
                "Content-MD5",
                "Content-Type",
                "Last-Modified"
            );

            foreach ($headers as $header) {
                $this->unsetHeader($header);
            }
        }
    }

    /**
     * @return bool
     */
    public function isInformational()
    {
        return $this->status >= 100 && $this->status < 200;
    }

    /**
     * @return void
     */
    public function send()
    {
        if (headers_sent() === false) {
            // Send status
            $format = (strpos(PHP_SAPI, "cgi") === 0 ? "Status: %s" : "$this->protocol %s");
            header(sprintf($format, static::getMessageForCode($this->getStatus())));

            // Send headers
            foreach ($this->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }

        $body = $this->getBody();
        if ($body instanceof \Closure) {
            call_user_func($body);

        } else {
            echo $body;
        }
    }

    /**
     * Get message for HTTP status code
     * @param int $status
     * @return string|null
     */
    public static function getMessageForCode($status)
    {
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        } else {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @throws \InvalidArgumentException
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;

        if ($this->status < 100 || $this->status > 600) {
            throw new \InvalidArgumentException("HTTP Status $this->status is invalid!");
        }
    }
}