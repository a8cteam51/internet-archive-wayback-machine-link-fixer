<?php

/**
 * Mixture of general functions, including compose, pipe, invoke and property
 * accessors.
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

namespace PinkCrab\FunctionConstructors\GeneralFunctions;

use Closure;
use TypeError;
use ArrayObject;
use PinkCrab\FunctionConstructors\Objects as Objects;

/**
 * Composes a function based on a set of callbacks.
 * All functions passed should have matching parameters.
 *
 * @param callable(mixed):mixed ...$callables
 * @return Closure(mixed):mixed
 */
function compose(callable ...$callables): Closure
{
    /**
     * @param mixed The value passed into the functions
     * @return mixed The final result.
     */
    return function ($e) use ($callables) {
        foreach ($callables as $callable) {
            $e = $callable($e);
        }
        return $e;
    };
}

/**
 * Composes a function based on a set of callbacks in reverse
 * All functions passed should have matching parameters.
 *
 * @param callable(mixed):mixed ...$callables
 * @return Closure(mixed):mixed
 */
function composeR(callable ...$callables): Closure
{
    /**
     * @param mixed The value passed into the functions
     * @return mixed The final result.
     */
    return function ($e) use ($callables) {
        foreach (\array_reverse($callables) as $callable) {
            $e = $callable($e);
        }
        return $e;
    };
}

/**
 * Compose a function which is escaped if the value returns as null.
 * This allows for safer composition.
 *
 * @param callable(mixed):mixed ...$callables
 * @return Closure(mixed):mixed
 */
function composeSafe(callable ...$callables): Closure
{
    /**
     * @param mixed The value passed into the functions
     * @return mixed|null The final result or null.
     */
    return function ($e) use ($callables) {
        foreach ($callables as $callable) {
            if (! is_null($e)) {
                $e = $callable($e);
            }
        }
        return $e;
    };
}

/**
 * Creates a strictly pure function.
 * Every return from every class, must pass a validation function
 * If any fail validation, null is returned.
 *
 * @param callable(mixed):bool $validator The validation function (b -> bool)
 * @param callable(mixed):mixed ...$callables The functions to execute (a -> a)
 * @return Closure(mixed):mixed
 */
function composeTypeSafe(callable $validator, callable ...$callables): Closure
{
    /**
     * @param mixed $e The value being passed through the functions.
     * @return mixed The final result.
     */
    return function ($e) use ($validator, $callables) {
        foreach ($callables as $callable) {
            // If invalid, abort and return null
            if (! $validator($e)) {
                return null;
            }
            // Run through callable.
            $e = $callable($e);

            // Check results and bail if invalid type.
            if (! $validator($e)) {
                return null;
            }
        }
        // Return the final result.
        return $e;
    };
}

/**
 * Alias for compose()
 *
 * @param mixed $value Value to be passed through the functions.
 * @param callable(mixed):mixed ...$callables Functions to be called.
 * @return mixed
 */
function pipe($value, callable ...$callables)
{
    return compose(...$callables)($value);
}

/**
 * The reverse of the functions passed into compose() (pipe())).
 * To give a more functional feel to some piped calls.
 *
 * @param mixed $value Value to be passed through the functions.
 * @param callable(mixed):mixed ...$callables
 * @return mixed
 */
function pipeR($value, callable ...$callables)
{
    return compose(...\array_reverse($callables))($value);
}

/**
 * Returns a callback for getting a property or element from an array/object.
 *
 * @param string $property
 * @return Closure(mixed):mixed
 */
function getProperty(string $property): Closure
{
    /**
     * @param mixed $data The array or object to attempt to get param.
     * @return mixed|null
     */
    return function ($data) use ($property) {
        if (is_array($data)) {
            return array_key_exists($property, $data) ? $data[ $property ] : null;
        } elseif (is_object($data)) {
            return property_exists($data, $property) ? $data->{$property} : null;
        } else {
            return null;
        }
    };
}

/**
 * Walks an array or object tree based on the nodes passed.
 * Will return whatever value at final node passed.
 * If any level returns null, process aborts.
 *
 * @param string ...$nodes
 * @return Closure(mixed[]|object):mixed
 */
function pluckProperty(string ...$nodes): Closure
{
    /**
     * @param mixed[]|object $data The array or object to attempt to get param.
     * @return mixed|null
     */
    return function ($data) use ($nodes) {
        foreach ($nodes as $node) {
            $data = getProperty($node)($data);

            if (is_null($data)) {
                return $data;
            }
        }
        return $data;
    };
}

/**
 * Returns a callable for a checking if a property exists.
 * Works for both arrays and objects (with public properties).
 *
 * @param string $property
 * @return Closure(mixed[]|object):mixed
 */
function hasProperty(string $property): Closure
{
    /**
     * @param mixed $data The array or object to attempt to get param.
     * @return mixed|null
     */
    return function ($data) use ($property): bool {
        if (is_array($data)) {
            return array_key_exists($property, $data);
        } elseif (is_object($data)) {
            return property_exists($data, $property);
        } else {
            return false;
        }
    };
}

/**
 * Returns a callable for a checking if a property exists and matches the passed value
 * Works for both arrays and objects (with public properties).
 *
 * @param string $property
 * @param mixed $value
 * @return Closure(mixed[]|object):bool
 */
function propertyEquals(string $property, $value): Closure
{
    /**
     * @param mixed $data The array or object to attempt to get param.
     * @return bool
     */
    return function ($data) use ($property, $value): bool {
        return pipe(
            $data,
            getProperty($property),
            \PinkCrab\FunctionConstructors\Comparisons\isEqualTo($value)
        );
    };
}

/**
 * Sets a property in an object or array.
 *
 * All object properties are passed as $object->{$property} = $value.
 * So no methods can be called using set property.
 * Will throw error is the property is protect or private.
 * Only works for public or dynamic properties.
 *
 * @param array<string,mixed>|ArrayObject<string,mixed>|object $store
     * @param string $property The property key.
 * @return Closure(mixed):(array<string,mixed>|ArrayObject<string,mixed>|object)
 */
function setProperty($store, string $property): Closure
{
    // If passed store is not an array or object, throw exception.
    if (! isArrayAccess($store) && ! is_object($store)) {
        throw new TypeError('Only objects or arrays can be constructed using setProperty.');
    }

    /**
     * @param mixed $value The value to set to keu.
     * @return array<string,mixed>|ArrayObject<string,mixed>|object The datastore.
     */
    return function ($value) use ($store, $property) {
        if (isArrayAccess($store)) {
            /** @phpstan-ignore-next-line */
            $store[ $property ] = $value;
        } else {
            $store->{$property} = $value;
        }

        return $store;
    };
}

/**
 * Returns a callable for created an array with a set key
 * sets the value as the result of a callable being passed some data.
 *
 * @param string $key
 * @param callable(mixed):mixed $value
 * @return Closure(mixed):mixed
 */
function encodeProperty(string $key, callable $value): Closure
{
    /**
     * @param mixed $data The data to pass through the callable
     * @return array
     */
    return function ($data) use ($key, $value): array {
        return array( $key => $value($data) );
    };
}

/**
 * Creates a callable for encoding an array or object,
 * from an array of encodeProperty functions.
 *
 * @param  array<string,mixed>|ArrayObject<string,mixed>|object $dataType
 * @return Closure(Closure(mixed):mixed ...$e):(array<string,mixed>|ArrayObject<string,mixed>|object)
 */
function recordEncoder($dataType): Closure
{
    /**
     * @param callable(mixed):mixed ...$encoders encodeProperty() functions.
     * @return Closure
     */
    return function (...$encoders) use ($dataType): Closure {
        /**
         * @param mixed $data The data to pass through the encoders.
         * @return array<string,mixed>|object The encoded object/array.
         */
        return function ($data) use ($dataType, $encoders) {
            foreach ($encoders as $encoder) {
                $key = array_keys($encoder($data))[0];
                // throw exception if key is int
                if (\is_numeric($key)) {
                    throw new TypeError('Encoders must user an array with a string key.');
                }

                $dataType = setProperty($dataType, $key)(array_values($encoder($data))[0]);
            }
            return $dataType;
        };
    };
}

/**
 * Partially applied callable invoker.
 *
 * @param callable(mixed):mixed $fn
 * @return Closure(mixed ...$a):mixed
 */
function invoker(callable $fn): Closure
{
    /**
     * @param mixed ...$args
     * @return mixed
     */
    return function (...$args) use ($fn) {
        return $fn(...$args);
    };
}

/**
 * Returns a function which always returns the value you created it with
 *
 * @param mixed $value The value you always want to return.
 * @return Closure(mixed):mixed
 */
function always($value): Closure
{
    /**
     * @param mixed $ignored Any values can be passed and ignored.
     * @return mixed
     */
    return function (...$ignored) use ($value) {
        return $value;
    };
}

/**
 * Returns a function for turning objects into arrays.
 * Only takes public properties.
 *
 * This has been moved to PinkCrab\FunctionConstructors\Objects\toArray()
 *
 * This will be removed in later versions.
 *
 * @return Closure(object):array<string, mixed>
 * @deprecated 0.2.0 Use PinkCrab\FunctionConstructors\Objects\toArray()
 */
function toArray(): Closure
{
    return Objects\toArray();
}

/**
 * Creates a function which will validate the data through a condition callable, then return
 * the results of passing the data through the callback.
 *
 * @param callable(mixed):bool  $condition
 * @param callable(mixed):mixed $then
 * @return \Closure(mixed):mixed
 */
function ifThen(callable $condition, callable $then): Closure
{
    /**
     * @param  mixed $value
     * @return mixed
     */
    return function ($value) use ($condition, $then) {
        return true === (bool) $condition($value)
            ? $then($value)
            : $value;
    };
}

/**
 * Creates a function which will validate the data through a condition callable, then return
 * the results of passing the data through the callback.
 * Has a required callback required for failing condition.
 *
 * @param callable(mixed):bool  $condition
 * @param callable(mixed):mixed $then
 * @param callable(mixed):mixed $else
 * @return \Closure
 */
function ifElse(callable $condition, callable $then, callable $else): Closure
{
    /**
     * @param  mixed $value
     * @return mixed
     */
    return function ($value) use ($condition, $then, $else) {
        return true === (bool) $condition($value)
            ? $then($value)
            : $else($value);
    };
}
