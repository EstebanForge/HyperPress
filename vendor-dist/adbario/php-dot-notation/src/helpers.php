<?php

/**
 * Dot - PHP dot notation access to arrays
 *
 * @author  Riku Särkinen <riku@adbar.io>
 * @link    https://github.com/adbario/php-dot-notation
 * @license https://github.com/adbario/php-dot-notation/blob/3.x/LICENSE.md (MIT License)
 */

use HyperPress\Adbar\Dot;

if (! function_exists('hyperpress_dot')) {
    /**
     * Create a new Dot object with the given items
     *
     * @param  mixed  $items
     * @param  bool  $parse
     * @param  non-empty-string  $delimiter
     * @return \HyperPress\Adbar\Dot<array-key, mixed>
     */
    function hyperpress_dot($items, $parse = false, $delimiter = ".")
    {
        return new Dot($items, $parse, $delimiter);
    }
}
