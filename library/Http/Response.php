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
 * This file is a refactored version of the Response object from the Slim
 * framework.
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

class Response extends MessageAbstract
{
    /**
     * @var int HTTP status code
     */
    protected $status;

    /**
     * @var Request
     */
    protected $request;

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
     * @param string $body
     * @param int $status
     * @param array $headers
     */
    public function __construct(Request $request, $body = "", $status = 200, $headers = array())
    {
        $this->request = $request;

        $this->setBody($body);
        $this->setStatus($status);
        $this->setHeaders($headers);
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
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;
    }

    /**
     * Finalize
     *
     * This prepares this response and returns an array
     * of [status, headers, body]. This array is passed to outer middleware
     * if available or directly to the Slim run method.
     *
     * @return array[int status, array headers, string body]
     */
    public function finalize()
    {
        // Prepare response
        if (in_array($this->status, array(204, 304))) {
            $this->setHeader("Content-Type", null);
            $this->setHeader("Content-Length", null);
            $this->setBody("");
        }

        return array($this->status, $this->getHeaders(), $this->getBody());
    }

    /**
     * This method prepares this response to return an HTTP Redirect response
     * to the HTTP client.
     *
     * @param string $destination
     * @param int $status
     */
    public function redirect($destination, $status = 302)
    {
        $this->setStatus($status);
        $this->setHeader("Location", $destination);
    }

	/**
	 * Set Last-Modified HTTP Response Header
	 *
	 * Set the HTTP 'Last-Modified' header and stop if a conditional
	 * GET request's `If-Modified-Since` header matches the last modified time
	 * of the resource. The `time` argument is a UNIX timestamp integer value.
	 * When the current request includes an 'If-Modified-Since' header that
	 * matches the specified last modified time, the application will send a
     * '304 Not Modified' response to the client.
	 *
	 * @param int $time The last modified UNIX timestamp
	 * @throws \InvalidArgumentException
	 */
	public function lastModified($time)
	{
        if (!is_integer($time)) {
            throw new \InvalidArgumentException(__CLASS__ . "::lastModified only accepts an integer UNIX timestamp value.");
        }

        $this->setHeader("Last-Modified", gmdate("D, d M Y H:i:s T", $time));

        if ($time === strtotime($this->request->getHeader("If-Modified-Since"))) {
            $this->setStatus(304);
        }
	}

	/**
	 * Set the etag header and stop if the conditional GET request matches.
	 * The `value` argument is a unique identifier for the current resource.
	 * The `type` argument indicates whether the etag should be used as a strong or
	 * weak cache validator.
	 *
	 * When the current request includes an 'If-None-Match' header with
	 * a matching etag, execution is immediately stopped. If the request
	 * method is GET or HEAD, a '304 Not Modified' response is sent.
	 *
	 * @param string $value The etag value
	 * @param string $type The type of etag to create; either "strong" or "weak"
	 * @throws \InvalidArgumentException If provided type is invalid
	 */
	public function etag($value, $type = "strong")
	{
		// Ensure type is correct
		if (!in_array($type, array("strong", "weak"))) {
			throw new \InvalidArgumentException("Invalid Slim::etag type. Expected \"strong\" or \"weak\".");
		}

		// Set etag value
		$value = '"' . $value . '"';
		if ($type === 'weak') {
			$value = 'W/'.$value;
		}
		$this['ETag'] = $value;

		// Check conditional GET
		if (($etagsHeader = $this->request->getHeader("If-None-Match"))) {
			$etags = preg_split('@\s*,\s*@', $etagsHeader);
			if (in_array($value, $etags) || in_array('*', $etags)) {
				$this->halt(304);
			}
		}
	}

	/**
	 * Set Expires HTTP response header
	 *
	 * The `Expires` header tells the HTTP client the time at which
	 * the current resource should be considered stale. At that time the HTTP
	 * client will send a conditional GET request to the server; the server
	 * may return a 200 OK if the resource has changed, else a 304 Not Modified
	 * if the resource has not changed. The `Expires` header should be used in
	 * conjunction with the `etag()` or `lastModified()` methods above.
	 *
	 * @param string|int    $time   If string, a time to be parsed by `strtotime()`;
	 *                              If int, a UNIX timestamp;
	 */
	public function expires($time)
	{
		if (is_string($time)) {
			$time = strtotime($time);
		}
		$this->setHeader("Expires", gmdate("D, d M Y H:i:s T", $time));
	}

    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    public function sendHeaders()
    {
        if (headers_sent() === false) {
            // Send status
            $format = (strpos(PHP_SAPI, "cgi") === 0 ? "Status: %s" : "HTTP/1.1 %s");
            header(sprintf($format, static::getMessageForCode($this->getStatus())));

            // Send headers
            foreach ($this->getHeaders() as $name => $all) {

                $individual = explode("\n", $all);
                foreach ($individual as $value) {
                    header("$name: $value", false);
                }
            }
        }
    }

    public function sendContent()
    {
        $isHead = ($this->request->getMethod() !== "HEAD");
        $noContent = in_array($this->status, array(204, 304));

        if (!$isHead && !$noContent) {
            echo $this->getBody();
        }
    }

    /**
     * Get message for HTTP status code
     * @param  int         $status
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
}