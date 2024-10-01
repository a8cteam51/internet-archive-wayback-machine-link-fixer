<?php

/**
 * Comparison functions
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
 * @since 0.0.1
 *
 * @template Number of int|float
 * @phpstan-template Number of int|float
 * @psalm-template Number of int|float
 */

declare(strict_types=1);

namespace PinkCrab\FunctionConstructors\Comparisons;

use Closure;

/**
 * Returns a callback for checking is a value is equal.
 * Works with String, Ints, Floats, Array, Objects & Bool
 *
 * @param mixed $a The value to compare against.
 * @return Closure(mixed $b):bool
 */
function isEqualTo($a): Closure
{
    /**
     * @param mixed $b The values to compare with
     * @return bool
     */
    return function ($b) use ($a): bool {
        if (! sameScalar($b, $a)) {
            return false;
        }

        switch (gettype($b)) {
            case 'string':
            case 'integer':
            case 'double':
            case 'boolean':
                $equal = $a === $b;
                break;
            case 'object':
                $equal = count(get_object_vars($a)) === count(array_intersect_assoc((array) $a, (array) $b));
                break;
            case 'array':
                $equal = count($a) === count(array_intersect_assoc($a, $b));
                break;
            default:
                $equal = false;
                break;
        }
        return $equal;
    };
}

/**
 * Returns a callable for checking if a value is not the same as the base ($a).
 *
 * @param mixed $a
 * @return Closure(mixed $b):bool
 */
function isNotEqualTo($a): Closure
{
    /**
     * @param mixed $b The values to compare with
     * @return bool
     */
    return function ($b) use ($a): bool {
        return ! isEqualTo($a)($b);
    };
}

/**
 * Returns a callable for checking the base is larger than comparison.
 * If the comparison value is not a int or float, will return false.
 *
 * @param Number $a
 * @return Closure(Number $b):bool
 */
function isGreaterThan($a): Closure
{
    /**
     * @param Number $b
     * @return bool
     */
    return function ($b) use ($a): bool {
        return isEqualIn(array( 'integer', 'double' ))(gettype($b))
            ? $a < $b : false;
    };
}

/**
 * Returns a callable for checking the base is smaller than comparison.
 * If the comparison value is not a int or float, will return false.
 *
 * @param Number $a
 * @return Closure(Number $b):bool
 */
function isLessThan($a): Closure
{
    /**
     * @param mixed $b
     * @return bool
     */
    return function ($b) use ($a): bool {
        return isEqualIn(array( 'integer', 'double' ))(gettype($b))
            ? $a > $b : false;
    };
}

/**
 * Returns a closure for checking if the value passes is
 * less than or equal to the comparison.
 *
 * @param Number $a The base value to compare against must be int or float.
 * @return Closure(Number): bool
 */
function isLessThanOrEqualTo($a): Closure
{
    /**
     * @param Number $b The base value to compare with must be int or float.
     * @return bool
     */
    return function ($b) use ($a): bool {
        return any(isEqualTo($a), isLessThan($a))($b);
    };
}

/**
 * Returns a closure for checking if the value passes is
 * greater than or equal to the comparison.
 *
 * @param Number $a
 * @return Closure(Number): bool
 */
function isGreaterThanOrEqualTo($a): Closure
{
    /**
     * @param Number $b
     * @return bool
     */
    return function ($b) use ($a): bool {
        return any(isEqualTo($a), isGreaterThan($a))($b);
    };
}

/**
 * Checks if a value is in an array of values.
 *
 * @param mixed[] $a
 * @return Closure(mixed $b):?bool
 */
function isEqualIn(array $a): Closure
{
    /**
     * @param mixed[] $b The array of values which it could be
     * @return bool
     */
    return function ($b) use ($a): ?bool {
        if (
            is_numeric($b) || is_bool($b) ||
            is_string($b) || is_array($b)
        ) {
            return in_array($b, $a, true);
        } elseif (is_object($b)) {
            return in_array(
                (array) $b,
                array_map(
                    function ($e): array {
                        return (array) $e;
                    },
                    $a
                ),
                true
            );
        } else {
            return null;
        }
    };
}

/**
 * Simple named function for ! empty()
 * Allows to be used in function composition.
 *
 * @param mixed $value The value
 * @return bool
 */
function notEmpty($value): bool
{
    return ! empty($value);
}

/**
 * Groups callbacks and checks they all return true.
 *
 * @param callable(mixed):bool ...$callables
 * @return Closure(mixed):bool
 */
function groupAnd(callable ...$callables): Closure
{
    /**
     * @param mixed $value
     * @return bool
     */
    return function ($value) use ($callables): bool {
        return (bool) array_reduce(
            $callables,
            function ($result, $callable) use ($value) {
                return (is_bool($result) && $result === false) ? false : $callable($value);
            },
            null
        );
    };
}

/**
 * Groups callbacks and checks they any return true.
 *
 * @param callable(mixed):bool ...$callables
 * @return Closure(mixed):bool
 */
function groupOr(callable ...$callables): Closure
{
    /**
     * @param mixed $value
     * @return bool
     */
    return function ($value) use ($callables): bool {
        return (bool) array_reduce(
            $callables,
            function ($result, $callable) use ($value) {
                return (is_bool($result) && $result === true) ? true : $callable($value);
            },
            null
        );
    };
}

/**
 * Creates a callback for checking if a value has the desired scalar type.
 *
 * @param string $source Type to compare with (bool, boolean, integer, object)
 * @return Closure(mixed):bool
 */
function isScalar(string $source): Closure
{
    /**
     * @param mixed $value
     * @return bool
     */
    return function ($value) use ($source) {
        return gettype($value) === $source;
    };
}



/**
 * Checks if all passed have the same scalar
 *
 * @param mixed ...$variables
 * @return bool
 */
function sameScalar(...$variables): bool
{
    return count(
        array_unique(
            array_map('gettype', $variables)
        )
    ) === 1;
}

/**
 * Checks if all the values passed are true.
 *
 * @param bool ...$variables
 * @return bool
 */
function allTrue(bool ...$variables): bool
{
    foreach ($variables as $value) {
        if (! is_bool($value) || $value !== true) {
            return false;
        }
    }
    return true;
}

/**
 * Checks if all the values passed are true.
 *
 * @param bool ...$variables
 * @return bool
 */
function anyTrue(bool ...$variables): bool
{
    foreach ($variables as $value) {
        if (is_bool($value) && $value === true) {
            return true;
        }
    }
    return false;
}

/**
 * Checks if the passed value is a boolean and false
 *
 * @param mixed $value
 * @return bool
 */
function isFalse($value): bool
{
    return is_bool($value) && $value === false;
}

/**
 * Checks if the passed value is a boolean and true
 *
 * @param mixed $value
 * @return bool
 */
function isTrue($value): bool
{
    return is_bool($value) && $value === true;
}

/**
 * Checks if the passed value is a float or int.
 *
 * @param mixed $value
 * @return boolean
 */
function isNumber($value): bool
{
    return is_float($value) || is_int($value);
}

/**
 * Alias for groupOr
 *
 * @param callable(mixed):bool ...$callables
 * @return Closure(mixed):bool
 */
function any(callable ...$callables): Closure
{
    return groupOr(...$callables);
}

/**
 * Alias for groupAnd
 *
 * @param callable(mixed):bool ...$callables
 * @return Closure(mixed):bool
 */
function all(callable ...$callables): Closure
{
    return groupAnd(...$callables);
}

/**
 * Returns a callable for giving the reverse boolean response.
 *
 * @param callable(mixed):bool $callable
 * @return Closure(mixed):bool
 */
function not(callable $callable): Closure
{
    /**
     * @param mixed $value
     * @return bool
     */
    return function ($value) use ($callable): bool {
        return ! (bool) $callable($value);
    };
}
