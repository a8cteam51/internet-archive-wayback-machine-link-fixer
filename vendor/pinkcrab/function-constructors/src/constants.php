<?php

/**
 * Collection of constants that can be used on specific functions.
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

/**
 * Constants used for Str\wordCount() => str_word_count()
 * https://www.php.net/manual/en/function.str-word-count.php
 */
if (!defined('WORD_COUNT_NUMBER_OF_WORDS')) {
    define('WORD_COUNT_NUMBER_OF_WORDS', 0);
}
if (!defined('WORD_COUNT_ARRAY')) {
    define('WORD_COUNT_ARRAY', 1);
}
if (!defined('WORD_COUNT_ASSOCIATIVE_ARRAY')) {
    define('WORD_COUNT_ASSOCIATIVE_ARRAY', 2);
}

// String function flags
if (!defined('STRINGS_CASE_INSENSITIVE')) {
    define('STRINGS_CASE_INSENSITIVE', 0x1);
}
if (!defined('STRINGS_CASE_SENSITIVE')) {
    define('STRINGS_CASE_SENSITIVE', 0x2);
}
if (!defined('STRINGS_BEFORE_NEEDLE')) {
    define('STRINGS_BEFORE_NEEDLE', 0x4);
}
if (!defined('STRINGS_AFTER_NEEDLE')) {
    define('STRINGS_AFTER_NEEDLE', 0x8);
}
