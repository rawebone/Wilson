<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Fixtures;

use Wilson\Routing\Dispatcher;

/**
 * Acts as a proxy over the Dispatcher object allowing us to test protected methods.
 */
class DispatcherProxy extends Dispatcher
{
    private $instance;

    function __call($name, $args)
    {
        return call_user_func_array(
            array($this->instance, $name),
            $args
        );
    }

    static function dispatcher(Dispatcher $dispatcher)
    {
        $new = new static($dispatcher->router, $dispatcher->sender);
        $new->instance = $dispatcher;
        return $new;
    }
}