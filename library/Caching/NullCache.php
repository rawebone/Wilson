<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Caching;

class NullCache implements CacheInterface
{
    /**
     * @param string $key
     * @return boolean
     */
    function has($key)
    {
        return false;
    }

    /**
     * @param string $key
     * @return mixed
     */
    function get($key)
    {
        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function set($key, $value)
    {
        // Noop
    }
}