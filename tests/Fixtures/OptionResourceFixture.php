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

class OptionResourceFixture
{
    function middlewareWithoutOption()
    {
    }

    /**
     * @option action int(min=1, max=2)
     */
    function middlewareWithOption()
    {
    }

    /**
     * @option name string
     */
    function controller()
    {
    }
}
