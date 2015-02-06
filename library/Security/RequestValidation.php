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

use ReflectionMethod;
use Wilson\Http\Request;

/**
 * RequestValidation acts as fast way of filtering out bad input, allowing us to
 * centralise and easily manage our parameters inside of our controllers/middleware.
 */
class RequestValidation
{
    /**
     * @option name arguments
     */
    const OPTION_REGEX = "/@option ([\\w]+) ([^\r\n]+)/";

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Filter $filter, Request $request)
    {
        $this->filter = $filter;
        $this->request = $request;
    }

    /**
     * Checks any options listed against controllers against the Filter object.
     *
     * @see Filter
     *
     * @param array $controllers
     * @return void
     * @throws ValidationException
     */
    public function validate(array $controllers)
    {
        $params = $this->request->getParams();

        foreach ($this->getOptionsFromMethods($controllers) as $option) {

            if (!isset($params[$option->name])) {
                continue;
            }

            // Replace the value placeholder in args
            $option->args[0] = $params[$option->name];

            $validated = call_user_func_array(
                array($this->filter, $option->filter),
                $option->args
            );

            if (!$validated) {
                throw ValidationException::invalid($option->name, $option->filter);
            }

            // Ensure that we use keep the validated parameter in the
            // request scope for when it gets used in the application
            // preventing issues with equality checks.
            $this->request->setParam($option->name, $validated);
        }
    }

    /**
     * Uses reflection to get the @option annotations from handlers that can
     * then be used for filtering.
     *
     * @todo Provide safe+fast way of validating requested filters exist
     *
     * @todo This could be cached if we have a reliable hash to work with
     *
     * @param array $methods
     * @return object[]
     */
    public function getOptionsFromMethods(array $methods)
    {
        $options = array();
        foreach ($methods as $method) {
            $reflection = new ReflectionMethod($method[0], $method[1]);

            $this->parseOptionFromComment($options, $reflection->getDocComment());
        }

        return $options;
    }

    /**
     * Appends options found in DocComment into the $option array.
     *
     * @param array $options
     * @param string $comment
     * @return void
     */
    public function parseOptionFromComment(array &$options, $comment)
    {
        if (preg_match_all(RequestValidation::OPTION_REGEX, $comment, $matches)) {
            for ($i = 0, $len = count($matches[1]); $i < $len; $i++) {
                $name = $matches[1][$i];
                $args = explode(" ", $matches[2][$i]);

                // The name of the filter to be run
                $filter = $args[0];

                // Replace the filter name in the arguments with a placeholder
                // for the parameter value to be filtered.
                $args[0] = null;

                $options[] = (object)compact("name", "filter", "args");
            }
        }
    }
}
