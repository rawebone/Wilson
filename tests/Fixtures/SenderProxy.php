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

use Wilson\Http\Sender;

/**
 * Acts as a proxy over the sender object allowing us to test protected methods
 * of the Sender object.
 */
class SenderProxy extends Sender
{
    private $instance;

    function __call($name, $args)
    {
        return call_user_func_array(
            array($this->instance, $name),
            $args
        );
    }

    static function sender(Sender $sender = null)
    {
        $new = new static();
        $new->instance = $sender ?: new Sender();
        return $new;
    }
}