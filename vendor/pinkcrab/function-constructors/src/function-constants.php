<?php

/**
 * Collection of constants used for simple functions.
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
 * @since 0.1.0
 *
 * @template Number of int|float
 * @phpstan-template Number of int|float
 * @psalm-template Number of int|float
 */

declare(strict_types=1);

namespace PinkCrab\FunctionConstructors;

class Functions
{
    ## Comparisons ##

    /**
     * Constants used for PinkCrab\FunctionConstructors\Comparisons\notEmpty(mixed $val):bool
     */
    public const NOT_EMPTY = 'PinkCrab\FunctionConstructors\Comparisons\notEmpty';

    /**
     * Constants used for PinkCrab\FunctionConstructors\Comparisons\isTrue(mixed $val):bool
     */
    public const IS_TRUE = 'PinkCrab\FunctionConstructors\Comparisons\isTrue';

    /**
     * Constants used for PinkCrab\FunctionConstructors\Comparisons\isFalse(mixed $val):bool
     */
    public const IS_FALSE = 'PinkCrab\FunctionConstructors\Comparisons\isFalse';

    /**
     * Constants used for PinkCrab\FunctionConstructors\Comparisons\isNumber(mixed $val):bool
     */
    public const IS_NUMBER = 'PinkCrab\FunctionConstructors\Comparisons\isNumber';


    ## Arrays ##

    /**
     * Constants used for PinkCrab\FunctionConstructors\Arrays\head(array $array):?mixed
     */
    public const ARRAY_HEAD = 'PinkCrab\FunctionConstructors\Arrays\head';

    /**
     * Constants used for PinkCrab\FunctionConstructors\Arrays\tail(array $array):?mixed
     */
    public const ARRAY_TAIL = 'PinkCrab\FunctionConstructors\Arrays\tail';

    ## String ##

    /**
     * Constants used for PinkCrab\FunctionConstructors\Strings\isBlank(mixed $value):bool
     */
    public const IS_BLANK = 'PinkCrab\FunctionConstructors\Strings\isBlank';
}
