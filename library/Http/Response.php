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
 * Represents an HTTP Response Message.
 *
 * This objects is derived from code taken from the Slim and Symfony frameworks.
 * Their licences are included with this project and links are provided below:
 *
 * @link https://github.com/rawebone/Wilson/LICENSE.SLIM
 * @link https://github.com/rawebone/Wilson/LICENSE.SYMFONY
 */
class Response extends MessageAbstract
{
    /**
     * HTTP response codes and messages.
     *
     * @var array
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
     * @var CacheControl
     */
    protected $cacheControl;

    /**
     * @var callable
     */
    protected $cacheMissedHandler;

    /**
     * @var string
     */
    protected $protocol = "HTTP/1.1";

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * Creates a new instance of the Response.
     */
    public function __construct()
    {
        $this->cacheControl = new CacheControl($this);
        $this->cacheMissedHandler = function () { };
    }

    /**
     * Invokes the handler to be called when the cache hit could not be made.
     *
     * @return void
     */
    public function cacheMissed()
    {
        call_user_func($this->cacheMissedHandler);
    }

    /**
     * Returns whether the status allows for the response allows for the body
     * to be returned to the user agent.
     *
     * @return boolean
     */
    public function isBodyAllowed()
    {
        return !($this->isInformational() || $this->status === 204 || $this->status === 304);
    }

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
     * Returns the Cache Control object for the response.
     *
     * @return CacheControl
     */
    public function getCacheControl()
    {
        return $this->cacheControl;
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
     * Helper method to set the response for HTML output.
     *
     * @param string|callable $content
     * @param int $status
     * @param array $headers
     * @return void
     */
    public function html($content, $status = 200, array $headers = array())
    {
        $this->setStatus($status);
        $this->setBody($content);

        if (!isset($headers["Content-Type"])) {
            $headers["Content-Type"] = "text/html";
        }
        $this->setHeaders($headers);
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
     * Sets the ETag value of the content.
     *
     * @param string $value
     * @param bool $weak
     * @return void
     */
    public function setETag($value, $weak = false)
    {
        $this->setHeader("ETag", ($weak ? "W/" : "") . "\"$value\"");
    }

    /**
     * Sets the protocol that the response should conform to.
     *
     * @param string $protocol
     * @return void
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
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
     * Sets the handler to be called when the request misses the cache. This
     * handler can then be used to set the body of the response/headers for
     * sending back to the client.
     *
     * @param callable $fn
     * @throws \InvalidArgumentException
     */
    public function whenCacheMissed($fn)
    {
        if (!is_callable($fn)) {
            throw new \InvalidArgumentException("\$fn should be a callable");
        }

        $this->cacheMissedHandler = $fn;
    }
}