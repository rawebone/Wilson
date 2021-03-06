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
 * This object prepares and sends a valid HTTP response based off of the given
 * Request object.
 */
class Sender
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
    protected $steps;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->steps = array(
            "ensureDate",
            "makeCacheHeaders",
            "cacheValidation",
            "prepareBody",
            "ensureProtocolMatch",
            "sendHeaders",
            "sendBody"
        );
    }

    /**
     * Sends the response back to the client.
     *
     * @return void
     */
    public function send()
    {
        foreach ($this->steps as $step) {
            $this->$step();
        }
    }

    /**
     * Ensure a date is sent with the Response.
     *
     * @link https://tools.ietf.org/html/rfc2616#section-14.18
     * @return void
     */
    protected function ensureDate()
    {
        if (!$this->response->hasHeader("Date")) {
            $this->response->setDateHeader("Date", new \DateTime());
        }
    }

    /**
     * Ensure that Cache-Control headers are defined, if required, on the
     * response.
     *
     * @see CacheControl
     * @return void
     */
    protected function makeCacheHeaders()
    {
        $this->response->getCacheControl()->makeCacheHeaders();
    }

    /**
     * Validates that a cached data is still valid and adjust the response
     * appropriately or begin deferred processing.
     *
     * @return void
     */
    protected function cacheValidation()
    {
        if ($this->request->isSafeMethod()
            && ($this->matchEntityTags() || $this->matchModified())) {
            $this->response->notModified();
        } else {
            $this->response->cacheMissed();
        }
    }

    /**
     * If the response can have a body, then set the content-length else
     * clear the body and content headers.
     *
     * @return void
     */
    protected function prepareBody()
    {
        $request  = $this->request;
        $response = $this->response;

        if (!$response->isBodyAllowed() || $request->getMethod() === "HEAD") {
            $response->unsetHeaders(array("Content-Type", "Content-Length"));
            $response->setBody("");

        } else if (is_string($body = $response->getBody())) {
            $response->setHeader("Content-Length", strlen($body));
        }
    }

    /**
     * Match the HTTP protocol of the request.
     *
     * @return void
     */
    protected function ensureProtocolMatch()
    {
        if ($this->response->getProtocol() !== $this->request->getProtocol()) {
            $this->response->setProtocol($this->request->getProtocol());
        }
    }

    /**
     * Sends the headers of the response to the client.
     *
     * @return void
     */
    protected function sendHeaders()
    {
        if (!headers_sent()) {
            $status = $this->response->getMessage() ?: $this->response->getStatus();
            header(sprintf("%s %s", $this->response->getProtocol(), $status));

            foreach ($this->response->getHeaders() as $name => $value) {
                header("$name: $value", true);
            }

            foreach ($this->response->getCookies() as $cookie) {
                header("Set-Cookie: $cookie", false);
            }
        }
    }

    /**
     * Sends the body of the response to the client.
     *
     * @return void
     */
    protected function sendBody()
    {
        $body = $this->response->getBody();

        // Response::setBody() makes an is_callable check when setting the value.
        // is_callable is a fairly heavy function due to the checks it has to
        // perform compared to is_string, so this is a safe and fast way, though
        // not necessarily intuitive way to structure the handling.
        if (is_string($body)) {
            echo $body;

        } else {
            call_user_func($body);
        }
    }

    /**
     * Returns whether the entity tags sent with the request match that
     * given by the response.
     *
     * @return bool
     */
    protected function matchEntityTags()
    {
        if (($responding = $this->response->getHeader("ETag"))) {
            $requested = $this->request->getETags();
            return $requested && (in_array($responding, $requested) || in_array("*", $requested));
        }

        return false;
    }

    /**
     * Returns whether a resource has been modified between two requests.
     *
     * @return bool
     */
    protected function matchModified()
    {
        $lastModified = $this->response->getHeader("Last-Modified");
        $modifiedSince = $this->request->getModifiedSince();

        if ($modifiedSince && $lastModified) {
            return strtotime($modifiedSince) >= strtotime($lastModified);
        }

        return false;
    }
}
