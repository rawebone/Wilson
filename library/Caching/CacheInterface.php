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

/**
 * CacheInterface implementers signify that they can store data between requests.
 *
 * @todo This needs to be replaced with the PSR-8 caching standard once released
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @return boolean
     */
    function has($key);

    /**
     * @param string $key
     * @return mixed|null
     */
    function get($key);

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function set($key, $value);
}