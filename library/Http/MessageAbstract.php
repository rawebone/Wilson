<?php

namespace Wilson\Http;

/**
 * MessageAbstract provides the basic handling for HTTP Request/Responses.
 *
 * This consolidates the Cookie and Header handling from the Slim frameworks
 * HTTP layer into a single package.
 */
abstract class MessageAbstract
{
    /**
     * @var string
     */
    private $body = "";

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var int
     */
    private $length = 0;

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        if ($this->hasHeader($name)) {
            return $this->headers[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setHeader($name, $value)
    {
        if (is_null($value)) {
            unset($this->headers[$name]);
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $content
     * @param boolean $replace
     * @return void
     */
    public function setBody($content, $replace = true)
    {
        if ($replace) {
            $this->body = $content;
        } else {
            $this->body .= (string)$content;
        }
        $this->length = strlen($this->body);
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }
}