<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Routing;

/**
 * UrlTools provides simple handling for creating URL matching regular
 * expressions and capturing the parameters of those URL's.
 */
class UrlTools
{
    /**
     * Returns a compiled url regex.
     *
     * @param string $url The parameterised url string
     * @param array $conditions An array containing the regular expression conditions to be merged
     * @return string
     */
    public function compile($url, array $conditions)
    {
        $replacer = function (array $match) use ($conditions) {
            $name = $match[2];
            $expr = (isset($conditions[$name]) ? $conditions[$name] : "[^/]+");

            return "(?<$name>$expr)";
        };

        $regex = preg_replace_callback("#(\\{([A-Za-z0-9]+)\\})+#", $replacer, $url);
        return $this->terminate($regex);
    }

    /**
     * Returns a terminated regular expression.
     *
     * @param string $expr
     * @return string
     */
    public function terminate($expr)
    {
        return "#^$expr$#";
    }

    /**
     * Returns whether the given URL regex matches the given query string.
     *
     * @param string $regex
     * @param string $queryString
     * @param array $params
     * @return boolean
     */
    public function match($regex, $queryString, array &$params)
    {
        if (preg_match($regex, rawurldecode($queryString), $matches) !== 1) {
            return false;
        }

        if ($matches) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
        }

        return true;
    }
}