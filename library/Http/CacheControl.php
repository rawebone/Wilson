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
 * Cache Control abstracts the difficulties of caching responses between HTTP
 * 1.0 and 1.1 and works in accordance with section 14.9 HTTP1.1 specification
 * for creation of the Cache-Control header.
 *
 * @https://tools.ietf.org/html/rfc2616#section-14.9
 */
class CacheControl
{
    /**
     * Stores the parts of the Cache-Control header.
     *
     * @var array
     */
    protected $parts;

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Response $response)
    {
        $this->parts = array();
        $this->response = $response;
    }

    /**
     * Sets the age of the request. $intermediaries sets the age that shared
     * proxies should respect.
     *
     * @param null|integer $userAgent
     * @param null|integer $intermediaries
     * @return $this
     */
    public function age($userAgent = null, $intermediaries = null)
    {
        $this->set("max-age", $userAgent);
        $this->set("s-maxage", $intermediaries);
        return $this;
    }

    /**
     * Instruct user agents not to cache the response.
     *
     * @return $this
     */
    public function doNotCache()
    {
        $this->parts["no-cache"] = true;
        return $this;
    }

    /**
     * Instructs the user agent and intermediaries not to convert the format
     * of the response body, such as image formats/quality, for performance
     * reasons.
     *
     * @return $this
     */
    public function doNotTransform()
    {
        $this->parts["no-transform"] = true;
        return $this;
    }

    /**
     * Instructs user agents and intermediaries not to cache any part of the
     * response.
     *
     * @return $this
     */
    public function doNotStore()
    {
        $this->parts["no-store"] = true;
        return $this;
    }

    /**
     * Sets the required cache headers on the response object.
     *
     * @return void
     */
    public function makeCacheHeaders()
    {
        $this->setCacheControl();
        $this->setExpires();
        $this->setPragma();
    }

    /**
     * Marks the response as being acceptable for caching by user agents and
     * intermediaries.
     *
     * @return $this
     */
    public function makePublic()
    {
        $this->parts["public"] = true;
        unset($this->parts["private"]);
        return $this;
    }

    /**
     * Marks the response as not being acceptable for caching by intermediaries
     * due to the content being specific to a single user. This does not imply
     * safety of the message content during transport.
     *
     * @return $this
     */
    public function makePrivate()
    {
        $this->parts["private"] = true;
        unset($this->parts["public"]);
        return $this;
    }

    /**
     * Marks whether non-shared/shared caches should revalidate each request
     * with the origin server.
     *
     * @param bool $userAgent
     * @param bool $intermediaries
     * @return $this
     */
    public function revalidate($userAgent = true, $intermediaries = true)
    {
        $this->set("must-revalidate", $userAgent);
        $this->set("proxy-revalidate", $intermediaries);
        return $this;
    }

    /**
     * Compiles and sets the Cache-Control header on the response.
     *
     * @return void
     */
    protected function setCacheControl()
    {
        $header = "";
        $haveValue = array("max-age", "s-maxage");

        foreach ($this->parts as $name => $value) {
            $header .= (in_array($name, $haveValue) ? "$name=$value" : $name) . ", ";
        }

        if ($header) {
            $header = substr($header, 0, strlen($header) - 2);
            $this->response->setHeader("Cache-Control", $header);
        }
    }

    /**
     * Sets the HTTP1.0 Expires header on the response if a max age has been
     * defined to attempt compatibility with older browsers.
     *
     * @return void
     */
    protected function setExpires()
    {
        if (isset($this->parts["max-age"])) {
            $expires = new \DateTime();
            $expires->add(new \DateInterval("PT{$this->parts["max-age"]}S"));
            $this->response->setDateHeader("Expires", $expires);

        } else if (isset($this->parts["no-cache"])) {
            $this->response->setHeader("Expires", -1);
        }
    }

    /**
     * Sets the HTTP1.0 Pragma header on the response if the no-cache option
     * has been specified to attempt compatibility with older browsers.
     */
    protected function setPragma()
    {
        if (isset($this->parts["no-cache"])) {
            $this->response->setHeader("Pragma", "no-cache");
        }
    }

    /**
     * Helper which sets/unsets a part of the cache control header.
     *
     * @param string $name
     * @param mixed|null $value
     */
    protected function set($name, $value)
    {
        if ($value) {
            $this->parts[$name] = $value;
        } else {
            unset($this->parts[$name]);
        }
    }
}