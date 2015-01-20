<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file has been shamelessly taken from phly/http.
 *
 * @link https://github.com/phly/http
 *
 * This file exists to allow overriding the various output-related functions
 * in order to test what happens during the `Server::listen()` cycle.
 *
 * These functions include:
 *
 * - headers_sent(): we want to always return false so that headers will be
 *   emitted, and we can test to see their values.
 * - header(): we want to aggregate calls to this function.
 * - printf(): we want to aggregate calls to this function as well; we cannot
 *   do the same with echo as it's a language construct, not a function.
 *
 * The Output class then aggregates that information for us, and the test
 * harness resets the values pre and post test.
 */

namespace Wilson\Http;

/**
 * Store output artifacts
 */
class HeaderStack
{
    /**
     * @var array
     */
    private static $data = array();

    /**
     * Reset state
     */
    public static function reset()
    {
        self::$data = array();
    }

    /**
     * Push a header on the stack
     *
     * @param string $header
     */
    public static function push($header)
    {
        self::$data[] = $header;
    }

    /**
     * Return the current header stack
     *
     * @return array
     */
    public static function stack()
    {
        return self::$data;
    }
}

/**
 * Have headers been sent?
 *
 * @return false
 */
function headers_sent()
{
    return false;
}

/**
 * Emit a header, without creating actual output artifacts
 *
 * @param string $value
 */
function header($value)
{
    HeaderStack::push($value);
}