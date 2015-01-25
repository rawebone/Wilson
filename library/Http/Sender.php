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
     * Sends the response back to the client.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function send(Request $request, Response $response)
    {
        $this->prepare($request, $response);

        if (!headers_sent()) {
            $this->sendHeaders($response);
        }

        $this->sendBody($response);
    }

    /**
     * Prepares the response based off of settings provided by the request.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function prepare(Request $request, Response $response)
    {
        if (!$response->hasHeader("Date")) {
            $response->setDateHeader("Date", new \DateTime());
        }

        $this->checkForModifications($request, $response);

        // Fix output content
        if ($response->isInformational()
            || $request->getMethod() === "HEAD"
            || $response->getStatus() === 204
            || $response->getStatus() === 304
        ) {
            $response->unsetHeaders(array("Content-Type", "Content-Length"));
            $response->setBody("");

        } else {
            if (is_string($body = $response->getBody())) {
                $response->setHeader("Content-Length", strlen($body));
            }
        }

        $this->checkProtocol($request, $response);
        $this->checkCacheControl($request, $response);
    }

    /**
     * Handles cache validation.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function checkForModifications(Request $request, Response $response)
    {
        if ($response->isNotModified($request)) {
            $response->notModified();
        } else {
            $response->cacheMissed();
        }
    }

    /**
     * Match the HTTP protocol of the request.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function checkProtocol(Request $request, Response $response)
    {
        if ($response->getProtocol() !== $request->getProtocol()) {
            $response->setProtocol($request->getProtocol());
        }
    }

    /**
     * On the older HTTP protocol we need to send more cache headers.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function checkCacheControl(Request $request, Response $response)
    {
        if ($request->getProtocol() === "HTTP/1.0" && $response->getHeader("Cache-Control") === "no-cache") {
            $response->setHeaders(array("Pragma" => "no-cache", "Expires" => -1));
        }
    }

    /**
     * Sends the headers of the response to the client.
     *
     * @param Response $response
     */
    protected function sendHeaders(Response $response)
    {
        $status = $response->getMessage() ?: $response->getStatus();
        header(sprintf("%s %s", $response->getProtocol(), $status));

        foreach ($response->getHeaders() as $name => $value) {
            header("$name: $value", true);
        }
    }

    /**
     * Sends the body of the response to the client.
     *
     * @return void
     */
    protected function sendBody(Response $response)
    {
        if (is_callable($body = $response->getBody())) {
            call_user_func($body);

        } else {
            echo $body;
        }
    }
}