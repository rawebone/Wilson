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

use Wilson\Http\Request;

/**
 * Filter acts as a barrier between passed input and expected input, allowing
 * us to easily create more secure applications. This is based primarily off
 * off the Shield Frameworks Filter object which in turn wraps the native
 * PHP functionality.
 */
class Filter
{
    /**
     * The types stored here need to be loosely compared when validating.
     *
     * @var int[]
     */
    protected $loose = array(
        FILTER_VALIDATE_INT,
        FILTER_VALIDATE_FLOAT
    );

    /**
     * Returns a sanitized string.
     *
     * @param mixed $value
     * @return null|string
     */
    public function string($value)
    {
        return htmlentities(filter_var($value, FILTER_SANITIZE_STRING));
    }

    /**
     * Validated $value as being an integer. Optionally can be validated as
     * being within a range.
     *
     * @param mixed $value
     * @param null|integer $min
     * @param null|integer $max
     * @return int|null
     */
    public function int($value, $min = null, $max = null)
    {
        $options = array(
            "min_range" => $min,
            "max_range" => $max
        );

        foreach ($options as $option => $val) {
            if (!is_int($val)) {
                unset($options[$option]);
            }
        }

        return $this->filterValidate($value, FILTER_VALIDATE_INT, compact("options"));
    }

    /**
     * Validated $value as being a float.
     *
     * @param mixed $value
     * @return float|null
     */
    public function float($value)
    {
        return $this->filterValidate($value, FILTER_VALIDATE_FLOAT);
    }

    /**
     * Validated $value as being an e-mail address.
     *
     * @param mixed $value
     * @return string|null
     */
    public function email($value)
    {
        return $this->filterValidate($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validated $value as being a URL.
     *
     * @param mixed $value
     * @return string|null
     */
    public function url($value)
    {
        return $this->filterValidate($value, FILTER_VALIDATE_URL);
    }

    protected function filterValidate($value, $type, $options = null)
    {
        $filtered = filter_var($value, $type, $options);

        // Ensure the filtered value and given value are still equivalent.
        // Some comparisons (like integer) need to be loose to work effectively.
        $match = in_array($type, $this->loose) ? $filtered == $value : $filtered === $value;

        return $match ? $filtered : null;
    }
}
