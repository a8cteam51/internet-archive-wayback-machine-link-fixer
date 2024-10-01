<?php

/**
 * Number functions.
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

namespace PinkCrab\FunctionConstructors\Numbers;

use Closure;
use InvalidArgumentException;
use PinkCrab\FunctionConstructors\Comparisons as C;

/**
 * Used to accumulate integers
 *
 * @param int $initial
 * @return Closure(int|null):(Closure|int)
 */
function accumulatorInt(int $initial = 0): Closure
{
    /**
     * @param int|null $value
     * @return Closure(int|null):(Closure|int)|int
     */
    return function (?int $value = null) use ($initial) {
        if (null !== $value) {
            $initial += $value;
        }
        return null !== $value ? accumulatorInt($initial) : $initial;
    };
}

/**
 * Used to accumulate floats
 *
 * @param float $initial
 * @return Closure(float|null):(Closure|float)
 */
function accumulatorFloat(float $initial = 0): Closure
{
    /**
     * @param float|null $value
     * @return Closure(float|null):(Closure|float)|float
     */
    return function (?float $value = null) use ($initial) {
        if (null !== $value) {
            $initial += $value;
        }
        return null !== $value ? accumulatorFloat($initial) : $initial;
    };
}

/**
 * Returns a function for adding a fixed amount.
 *
 * @param Number $initial Defaults to 0
 * @return Closure(Number):Number
 * @throws InvalidArgumentException If neither int or float passed.
 */
function sum($initial = 0): Closure
{
    if (! C\isNumber($initial)) {
        throw new InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param Number $value
     * @return int|float
     */
    return function ($value) use ($initial) {
        return $initial + $value;
    };
}


/**
 * Returns a function for adding a fixed amount.
 *
 * @param int $initial Defaults to 0
 * @return Closure(Number):Number
 * @throws InvalidArgumentException If neither int or float passed.
 */
function subtract($initial = 0): Closure
{
    if (! C\isNumber($initial)) {
        throw new InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param Number $value
     * @return int|float
     */
    return function ($value) use ($initial) {
        return $value - $initial;
    };
}


/**
 * Returns a function for multiplying a fixed amount.
 *
 * @param Number $initial Defaults to 1
 * @return Closure(Number):Number
 * @throws InvalidArgumentException
 */
function multiply($initial = 1): Closure
{
    if (! C\isNumber($initial)) {
        throw new InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param Number $value
     * @return Number
     */
    return function ($value) use ($initial) {
        return $value * $initial;
    };
}



/**
 * Returns a function for divideBy a fixed amount.
 *
 * @param float $divisor The value to divide the passed value by
 * @return Closure(Number):float
 * @throws InvalidArgumentException If neither int or float passed.
 */
function divideBy($divisor = 1): Closure
{
    if (! C\isNumber($divisor)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param float $value
     * @return float
     */
    return function ($value) use ($divisor): float {
        return $value / $divisor;
    };
}

/**
 * Returns a function for divideInto a fixed amount.
 *
 * @param float $dividend The value to divide the passed value by
 * @return Closure(Number):float
 * @throws InvalidArgumentException If neither int or float passed.
 */
function divideInto($dividend = 1): Closure
{
    if (! C\isNumber($dividend)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param float $value
     * @return float
     */
    return function ($value) use ($dividend): float {
        return $dividend / $value;
    };
}

/**
 * Returns a function for getting the remainder with a fixed divisor.
 *
 * @param float $divisor
 * @return Closure(Number):float
 * @throws InvalidArgumentException If neither int or float passed.
 */
function remainderBy($divisor = 1): Closure
{
    if (! C\isNumber($divisor)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param float $value
     * @return float
     */
    return function ($value) use ($divisor): float {
        return $value % $divisor;
    };
}

/**
 * Returns a function for getting the remainder with a fixed dividend.
 *
 * @param float $dividend
 * @return Closure(Number):float
 * @throws InvalidArgumentException If neither int or float passed.
 */
function remainderInto($dividend = 1): Closure
{
    if (! C\isNumber($dividend)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param float $value
     * @return float
     */
    return function ($value) use ($dividend): float {
        return $dividend % $value;
    };
}

/**
 * Returns a function for checking if a number is multiple of the pre defined value.
 *
 * @param Number $multiple
 * @return Closure(Number):bool
 * @throws InvalidArgumentException If neither int or float passed.
 */
function isMultipleOf($multiple): Closure
{
    if (! C\isNumber($multiple)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param Number $value
     * @return bool
     * @throws InvalidArgumentException If neither int or float passed.
     */
    return function ($value) use ($multiple): bool {
        if (! C\isNumber($value)) {
            throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
        }

        // Return false if 0
        if ($value === 0) {
            return false;
        }

        return $value % $multiple === 0;
    };
}

/**
 * Returns a function for checking if a number has a factor of another number.
 *
 * @param int $factor
 * @return Closure(int):bool
 */
function isFactorOf(int $factor): Closure
{
    /**
     * @param int $value
     * @return bool
     * @throws InvalidArgumentException If neither int or float passed.
     */
    return function (int $value) use ($factor): bool {
        // Return false if 0
        if ($value === 0) {
            return false;
        }

        return $factor % $value === 0;
    };
}


/**
 * Returns a function for getting the remainder with a fixed dividend.
 *
 * @param int $precision Number of decimal places.
 * @return Closure(Number):float
 * @throws InvalidArgumentException If neither int or float passed.
 */
function round($precision = 1): Closure
{
    if (! C\isNumber($precision)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int)');
    }

    /**
     * @param Number $value
     * @return float
     * @throws InvalidArgumentException If neither int or float passed.
     */
    return function ($value) use ($precision): float {
        if (! C\isNumber($value)) {
            throw new \InvalidArgumentException("Num\\round() only accepts a valid Number ( Int|Float -> Float )");
        }
        return \round(\floatval($value), $precision);
    };
}

/**
 * Returns a closure for raising the power of the passed by value, by a pre defined exponent.
 *
 * @param Number $exponent
 * @return Closure(Number):Number
 * @throws InvalidArgumentException If neither int or float passed.
 */
function power($exponent): Closure
{
    if (! C\isNumber($exponent)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int) for the exponent');
    }

    /**
     * @param Number $value
     * @return Number
     * @throws InvalidArgumentException If neither int or float passed.
     */
    return function ($value) use ($exponent) {
        if (! C\isNumber($value)) {
            throw new \InvalidArgumentException('Num\\power() only accepts a valid Number ( Int|Float )');
        }
        return \pow($value, $exponent);
    };
}

/**
 * Returns closure for getting the pre defined root of a passed value.
 *
 * @param Number $root
 * @return Closure(Number):Number
 * @throws InvalidArgumentException If neither int or float passed.
 */
function root($root): Closure
{
    if (! C\isNumber($root)) {
        throw new \InvalidArgumentException(__FUNCTION__ . 'only accepts a Number (Float or Int) for the root');
    }

    /**
     * @param Number $value
     * @return Number
     * @throws InvalidArgumentException If neither int or float passed.
     */
    return function ($value) use ($root) {
        if (! C\isNumber($value)) {
            throw new \InvalidArgumentException('Num\\root() only accepts a valid Number ( Int|Float )');
        }
        return pow($value, (1 / $root));
    };
}
