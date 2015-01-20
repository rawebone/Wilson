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
     * Returns whether the status is in the Client Error range.
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Returns whether the status is in the Informational range.
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->status >= 100 && $this->status < 200;
    }

    /**
     * Determines if the requested resource has been modified since the
     * last request, allowing us to optimise the response. This is based
     * off of Symfony\Component\HttpFoundation\Response::isNotModified().
     *
     * @param Request $request
     * @return boolean
     */
    public function isNotModified(Request $request)
    {
        if (!$request->isSafeMethod()) {
            return false;
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

        return $notModified;
    }

    /**
     * Returns whether the request status is 200.
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->status === 200;
    }

    /**
     * Returns whether the status is in the Successful range.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Returns whether the status is in the Redirection range.
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Returns whether the status is in the Server Error range.
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Get message for HTTP status code.
     *
     * @return string|null
     */
    public function getMessage()
    {
        $status = $this->status;
        return isset(self::$messages[$status]) ? self::$messages[$status] : null;
    }

    /**
     * Returns the HTTP protocol of the response.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Returns the status of the response.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Helper method to set the values of the response appropriate for a JSON
     * message. The inspiration for this method came from the Symfony
     * JsonResponse.
     *
     * @see Symfony\Component\HttpFoundation\JsonResponse
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return void
     */
    public function json($data, $status = 200, array $headers = array(), $options = 0)
    {
        $this->setStatus($status);

        // Only set Content Type if not already set by the user.
        if (!isset($headers["Content-Type"])) {
            $headers["Content-Type"] = "application/json";
        }
        $this->setHeaders($headers);

        // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
        $opts = $options ?: JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $this->setBody(json_encode($data, $opts));
    }

    /**
     * Helper method to set all of the values of the response.
     *
     * @param string|callable $content
     * @param int $status
     * @param array $headers
     * @return void
     */
    public function make($content, $status = 200, array $headers = array())
    {
        $this->setStatus($status);
        $this->setBody($content);
        $this->setHeaders($headers);
    }

    /**
     * Sets the response to report not modified.
     *
     * @return void
     */
    public function notModified()
    {
        $this->setStatus(304);

        // These headers are not allowed to be included with a 304 response
        $this->unsetHeaders(array(
            "Allow",
            "Content-Encoding",
            "Content-Language",
            "Content-Length",
            "Content-MD5",
            "Content-Type",
            "Last-Modified"
        ));
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
        if ($this->isNotModified($request)) {
            $this->notModified();
        }

        // Fix output content
        if ($this->isInformational()
            || $request->getMethod() === "HEAD"
            || $this->status === 204
            || $this->status === 304
        ) {
            $this->unsetHeaders(array("Content-Type", "Content-Length"));
            $this->setBody("");

        } else {
            if (is_string($body = $this->getBody())) {
                $this->setHeader("Content-Length", strlen($body));
            }
        }

        $this->checkProtocol($request);
        $this->checkCacheControl();
    }

    /**
     * Sets the response to redirect to the given location.
     *
     * @param string $location
     * @param int $status
     * @return void
     */
    public function redirect($location, $status = 302)
    {
        $this->setStatus($status);
        $this->setHeader("Location", $location);
    }

    /**
     * Sends the response headers and body to the client.
     *
     * @return void
     */
    public function send()
    {
        if (headers_sent() === false) {
            // Send status
            $format = (strpos(PHP_SAPI, "cgi") === 0 ? "Status: %s" : "$this->protocol %s");
            header(sprintf($format, $this->getMessage()));

            // Send headers
            foreach ($this->getHeaders() as $name => $value) {
                header("$name: $value");
            }
        }

        $this->sendContent();
    }

    /**
     * Sets the date at which the content will expire.
     *
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
     * Sets the last modified date of the content.
     *
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
            $this->setHeader("Last-Modified", $date->format("D, d M Y H:i:s T"));
        }
    }

    /**
     * Sets the ETag value of the content.
     *
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
     * Sets the status of the response.
     *
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

    /**
     * Match the HTTP protocol of the request.
     *
     * @param Request $request
     * @return void
     */
    protected function checkProtocol(Request $request)
    {
        if ($this->protocol !== $request->getProtocol()) {
            $this->protocol = $request->getProtocol();
        }
    }

    /**
     * On the older HTTP protocol we need to send more cache headers.
     *
     * @return void
     */
    protected function checkCacheControl()
    {
        if ($this->protocol === "HTTP/1.0" && $this->getHeader("Cache-Control") === "no-cache") {
            $this->setHeaders(array("Pragma" => "no-cache", "Expires" => -1));
        }
    }

    /**
     * Sends the body of the response to the client.
     *
     * @return void
     */
    protected function sendContent()
    {
        $body = $this->getBody();
        if (is_callable($body)) {
            call_user_func($body);

        } else {
            echo $body;
        }
    }
}