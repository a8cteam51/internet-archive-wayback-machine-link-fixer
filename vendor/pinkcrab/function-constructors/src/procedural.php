<?php

/**
 * Procedural wrappers for various functions.
 *
 * This file is part of PinkCrab Function Constructors.
 *
 * PinkCrab Function Constructors is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2
 * of the License, or (at your option) any later version.
 *
 * PinkCrab Function Constructors is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with PinkCrab Function Constructors.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html
 * @package PinkCrab\FunctionConstructors
 */

declare(strict_types=1);

use PinkCrab\FunctionConstructors\Arrays as Arr;

if (! function_exists('stringContains')) {
    /**
     * Checks if a string contains a sub string
     *
     * @param string $haystack The string to search within.
     * @param string $needle The sub string to look for.
     * @return bool
     */
    function stringContains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (! function_exists('array_flatten')) {
    /**
     * Flattens an array to desired depth.
     * doesn't preserve keys
     *
     * @param mixed[] $array The array to flatten
     * @param int|null $n The depth to flatten the array, if null will flatten all arrays.
     * @return mixed[]
     */
    function arrayFlatten(array $array, ?int $n = null): array
    {
        return Arr\flattenByN($n)($array);
    }
}

if (! function_exists('toObject')) {
    /**
     * Simple mapper for turning arrays into stdClass objects.
     *
     * @param array<string|int, mixed> $array
     * @return object
     */
    function toObject(array $array)
    {
        return Arr\toObject(new stdClass())($array);
    }
}

if (! function_exists('invokeCallable')) {
    /**
     * Used to invoke a callable.
     *
     * @param callable(mixed ...$args):mixed $fn
     * @param mixed ...$args
     * @return mixed
     */
    function invokeCallable(callable $fn, ...$args)
    {
        return $fn(...$args);
    }
}

if (! function_exists('isArrayAccess')) {
    /**
     * Checks if an array or an object which has array like access.
     *
     * @source https://stackoverflow.com/questions/12346479/how-to-check-for-arrayness-in-php
     * @param mixed $var
     * @return bool
     */
    function isArrayAccess($var)
    {
        return is_array($var) || $var instanceof \ArrayAccess;
    }
}
