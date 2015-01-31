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
 * This implementation of the Cookie object is largely based off of that present
 * in Symfony HttpFoundation but has been simplified down to be immutable without
 * the large amount of getters.
 *
 * @see Symfony\Component\HttpFoundation\Cookie
 *
 * @property string $name
 * @property mixed $value
 * @property string $domain
 * @property \DateTime|null $expire
 * @property boolean $secure
 * @property boolean $httpOnly
 */
class Cookie
{
    protected $name;
    protected $value;
    protected $domain;
    protected $expire;
    protected $path;
    protected $secure;
    protected $httpOnly;

    /**
     * @param string $name
     * @param string|null $value
     * @param \DateTime|null $expire
     * @param string $path
     * @param string|null $domain
     * @param boolean $secure
     * @param boolean $httpOnly
     */
    public function __construct($name, $value = null, \DateTime $expire = null, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException("The cookie name cannot be empty.");
        }

        // from PHP source code
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new \InvalidArgumentException(sprintf("The cookie name '%s' contains invalid characters.", $name));
        }

        if ($expire) {
            $expire = clone $expire;
            $expire->setTimezone(new \DateTimeZone("UTC"));
        }

        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = $expire;
        $this->path = empty($path) ? "/" : $path;
        $this->secure = (bool)$secure;
        $this->httpOnly = (bool)$httpOnly;
    }

    /**
     * Returns the cookie as a string.
     *
     * @return string
     */
    public function __toString()
    {
        $str = urlencode($this->name) . "=";

        if ((string)$this->value === "") {
            $str .= "deleted; expires=" . gmdate("D, d-M-Y H:i:s T", time() - 31536001);
        } else {
            $str .= urlencode($this->value);

            if ($this->expire !== null) {
                $str .= "; expires=" . $this->expire->format("D, d-M-Y H:i:s T");
            }
        }

        foreach (array("path", "domain", "secure", "httpOnly") as $field) {
            if ($this->$field) {
                $str .= "; " . strtolower($field) . (is_string($this->$field) ? "={$this->$field}" : "");
            }
        }

        return $str;
    }

    /**
     * Returns the value of the property, or null if it does not exist.
     *
     * @param string $name
     * @return null|string
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException("Cookie does not have property $name");
        }

        return $this->$name;
    }
}