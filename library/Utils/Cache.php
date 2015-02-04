<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Utils;

/**
 * Cache provides an array file cache mechanism. This is sub-optimal for heavy
 * usage as it incurs a Disk IO + PHP parse hit, however this is currently
 * better than building the routing table from fresh.
 */
class Cache
{
    /**
     * Allows us to check whether we should save to disk.
     *
     * @var bool
     */
    protected $amended = false;

    /**
     * The file that we are going to store to.
     *
     * @var string
     */
    protected $file;

    /**
     * The current state of the cache.
     *
     * @var array
     */
    protected $state = array();

    /**
     * Creates a new instance of the cache object, loading from the provided
     * cache file if $file is a path and the path exists.
     *
     * @param string|null $file
     */
    public function __construct($file)
    {
        $this->file = $file;

        if ($this->file && is_file($this->file)) {
            $this->state = include $this->file;
        }
    }

    /**
     * Saves the state of the cache to disk before the application terminates,
     * if the state has changed.
     */
    public function __destruct()
    {
        if ($this->file && $this->amended) {
            file_put_contents($this->file, "<?php return " . var_export($this->state, true) . ";");
        }
    }

    /**
     * Returns a cached value if available, or null.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        return (isset($this->state[$key]) ? $this->state[$key] : null);
    }

    /**
     * Caches a value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->amended = true;
        $this->state[$key] = $value;
    }
}
