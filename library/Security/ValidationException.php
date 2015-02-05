<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Security;

/**
 * ValidationException will be thrown when a parameter is deemed invalid.
 */
class ValidationException extends \Exception
{
    /**
     * The expected type/regex of the input.
     *
     * @var string
     */
    public $expected;

    /**
     * The name of the input.
     *
     * @var string
     */
    public $param;

    public static function invalid($param, $expected)
    {
        $msg = "Parameter $param is invalid, $expected is expected";
        $ex  = new static($msg);
        $ex->param = $param;
        $ex->expected = $expected;

        return $ex;
    }
}
