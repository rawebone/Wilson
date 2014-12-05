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
 * This code is a refactored version of the Cookie handling from the Slim
 * framework, normalising the Slim\Helper\Set functionality into the object.
 * Once the handling is established an number of these methods can be removed.
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
class Cookies
{
	/**
	 * @var array
	 */
	protected $data = array();

    /**
     * @var array
     */
    protected $defaults = array(
        "value"    => "",
        "domain"   => null,
        "path"     => null,
        "expires"  => null,
        "secure"   => false,
        "httponly" => false
    );

	/**
	 * @param array $items
	 */
	public function __construct($items = array())
	{
		$this->replace($items);
	}

    /**
     * Set cookie
     *
     * The second argument may be a single scalar value, in which case
     * it will be merged with the default settings and considered the `value`
     * of the merged result.
     *
     * The second argument may also be an array containing any or all of
     * the keys shown in the default settings above. This array will be
     * merged with the defaults shown above.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        if (is_array($value)) {
            $cookieSettings = array_replace($this->defaults, $value);
        } else {
            $cookieSettings = array_replace($this->defaults, array("value" => $value));
        }

        $this->data[$key] = $cookieSettings;
    }

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if ($this->has($key)) {
			$isInvokable = is_object($this->data[$key]) && method_exists($this->data[$key], "__invoke");

			return $isInvokable ? $this->data[$key]($this) : $this->data[$key];
		}

		return $default;
	}

	/**
	 * @param array $items
	 */
	public function replace(array $items)
	{
		foreach ($items as $key => $value) {
			$this->set($key, $value);
		}
	}

	/**
	 * @return array
	 */
	public function all()
	{
		return $this->data;
	}

	/**
	 * @return array
	 */
	public function keys()
	{
		return array_keys($this->data);
	}

	/**
	 * @param string $key
	 * @return boolean
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->data);
	}

    /**
     * Remove cookie
     *
     * This will actually *set* a cookie with an expiration date in the past.
	 * This expiration date will force the client-side cache to remove its
	 * cookie with the given name and settings.
     *
     * @param string $key Cookie name
     * @param array $settings Optional cookie settings
     */
    public function remove($key, $settings = array())
    {
        $settings["value"] = "";
        $settings["expires"] = time() - 86400;
        $this->set($key, array_replace($this->defaults, $settings));
    }
}