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
 * FileCache is a sub-optimal way of caching data to disk, however it makes
 * the best sense for the framework as it requires no dependencies.
 */
class FileCache implements CacheInterface
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $state = array();

    public function __construct($file)
    {
        $this->file = $file;

        $this->load();
    }

    public function __destruct()
    {
        $this->store();
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->state[$key]);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->state[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->state[$key] = $value;
    }

    protected function load()
    {
        if (is_file($this->file)) {
            $this->state = include $this->file;
        }
    }

    protected function store()
    {
        file_put_contents($this->file, var_export($this->state, true));
    }
}