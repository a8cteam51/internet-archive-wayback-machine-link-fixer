<?php

/**
 * Composable strings functions.
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

namespace PinkCrab\FunctionConstructors\Strings;

use Closure;
use PinkCrab\FunctionConstructors\Comparisons as C;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;

/**
 * Creates a callable for wrapping a string.
 * By defaults uses opening as closing, if no closing defined.
 *
 * @param string $opening
 * @param string|null $closing
 * @return Closure(string):string
 */
function wrap(string $opening, ?string $closing = null): Closure
{
    /**
     * @param string $string
     * @return string
     */
    return function (string $string) use ($opening, $closing): string {
        return \sprintf('%s%s%s', $opening, $string, $closing ?? $opening);
    };
}

/**
 * Creates a callable for slicing a string
 *
 * Uses substr()
 *
 * @param int $start start position
 * @param int|null $finish end position
 * @return Closure(string):string
 */
function slice(int $start, ?int $finish = null): Closure
{
    /**
     * @param string $string
     * @return string
     */
    return function (string $string) use ($start, $finish): string {
        return ! $finish
            ? substr($string, $start)
            : substr($string, $start, $finish);
    };
}

/**
 * Creates a callable for prepending to a string.
 *
 * @param string $prepend
 * @return Closure(string):string
 */
function prepend(string $prepend): Closure
{
    /**
     * @param string $string
     * @return string
     */
    return function (string $string) use ($prepend): string {
        return \sprintf('%s%s', $prepend, $string);
    };
}

/**
 * Creates a callable for appending to a string.
 *
 * @param string $append
 * @return Closure(string):string
 */
function append(string $append): Closure
{
    /**
     * @param string $string
     * @return string
     */
    return function (string $string) use ($append): string {
        return \sprintf('%s%s', $string, $append);
    };
}

/**
 * Returns a callable for formatting a string with a defined set of rules
 *
 * @param string $template
 * @return Closure(array<string, mixed>):string
 */
function vSprintf(string $template): Closure
{
    /**
     * @param array<string, mixed> $values
     * @return string Will return original string if false.
     */
    return function (array $values = array()) use ($template): string {
        return \vsprintf($template, $values);
    };
}

/**
 * Creates a double curried find to replace.
 *
 * @param string $find Value to look for
 * @return Closure(string):Closure
 */
function findToReplace(string $find): Closure
{
    /**
     * @param string $replace value to replace with
     * @return Closure(string):string
     */
    return function (string $replace) use ($find): Closure {
        /**
         * @param string $subject String to carry out find and replace.
         * @return string
         */
        return function ($subject) use ($find, $replace): string {
            return \str_replace($find, $replace, $subject);
        };
    };
}

/**
 * Creates a Closure to find and replace within a string.
 *
 * @param string  $find
 * @param string  $replace
 * @return Closure(string):string
 */
function replaceWith(string $find, string $replace): Closure
{
    /**
     * @param string $source
     * @return string
     */
    return function ($source) use ($find, $replace): string {
        return \str_replace($find, $replace, $source);
    };
}

/**
 * Returns a callable that can replace a sub string with a pre defined value.
 *
 * @param string $replace The value to replace in passed string
 * @param int $offset The offset to start, negative numbers count back from end.
 * @param int|null $length Number of chars to stop replacing at end of replacement.
 * @return Closure(string):string
 */
function replaceSubString(string $replace, int $offset = 0, ?int $length = null): Closure
{
    /**
     * @param string $string
     * @return string
     */
    return function (string $string) use ($replace, $offset, $length): string {
        return $length
            ? \substr_replace($string, $replace, $offset, $length)
            : \substr_replace($string, $replace, $offset);
    };
}

/**
 * Creates a callable for checking if a string starts with
 *
 * @param string $find The value to look for.
 * @return Closure(string):bool
 */
function startsWith(string $find): Closure
{
    /**
     * @param string $source
     * @return bool
     */
    return function (string $source) use ($find): bool {
        return (\substr($source, 0, \strlen($find)) === $find);
    };
}

/**
 * Creates a callable for checkin if a string ends with
 *
 * @param string $find The value to look for.
 * @return Closure(string):bool
 */
function endsWith(string $find): Closure
{
    /**
     * @param string $source
     * @return bool
     */
    return function (string $source) use ($find): bool {
        if (\strlen($find) === 0) {
            return true;
        }
        return (\substr($source, -\strlen($find)) === $find);
    };
}

/**
 * Creates a callable for checking if a string contains. using stringContains
 *
 * @param string $needle The value to look for.
 * @return Closure(string):bool
 */
function contains(string $needle): Closure
{
    /**
     * @param string $haystack String to look in.
     * @return bool
     */
    return function (string $haystack) use ($needle): bool {
        return \stringContains($haystack, $needle);
    };
}

/**
 * Creates a callable for checking if a string contains using preg_match.
 *
 * @param string $pattern
 * @return Closure(string):bool
 */
function containsPattern(string $pattern): Closure
{
    /**
     * @param string $source String to look in.
     * @return bool
     */
    return function (string $source) use ($pattern): bool {
        return (bool) \preg_match($pattern, $source);
    };
}

/**
 * Splits a string with a pattern
 *
 * @param string $pattern
 * @return Closure(string):?string[]
 */
function splitPattern(string $pattern): Closure
{
    /**
     * @param string $name
     * @return string[]
     */
    return function (string $string) use ($pattern): ?array {
        return \preg_split($pattern, $string) ?: null;
    };
}

/**
 * Converts a number (loose type) to a string representation of a float.
 *
 * @param string|int|float $precision Number of decimal places
 * @param string $point The decimal separator
 * @param string $thousands The thousand separator.
 * @return Closure(string|int|float):string
 */
function decimalNumber($precision = 2, $point = '.', $thousands = ''): Closure
{
    /**
     * @param string|int|float $number
     * @return string
     */
    return function ($number) use ($precision, $point, $thousands): string {
        return \is_numeric($number)
            ? \number_format((float) $number, (int) $precision, $point, $thousands)
            : '';
    };
}



/**
 * Returns a callable for adding C slashes to a string based on a defined char list.
 *
 * @param string $charList The Char list to add slashes too.
 * @return Closure(string):string
 */
function addSlashes(string $charList): Closure
{
    /**
     * @param string $string The string to have char, slash escaped.
     * @return string
     */
    return function (string $string) use ($charList): string {
        return \addcslashes($string, $charList);
    };
}

/**
 * Returns a callable for splitting a string by a set amount.
 *
 * @param non-empty-string $separator The char to split by.
 * @param int $limit The number of groups to split into.
 * @return Closure(string):array<string> The parts.
 */
function split(string $separator, int $limit = PHP_INT_MAX): Closure
{
    /**
     * @param string $string The string to be split
     * @return array<int, string>
     */
    return function (string $string) use ($separator, $limit): array {
        $chunks = explode($separator, $string, $limit);
        return is_array($chunks) ? $chunks : array(); // @phpstan-ignore-line, inconsistent errors with differing php versions.
    };
}

/**
 * Returns a callable for splitting a string by a set amount.
 *
 * @param int $length The length to split the string up with.
 * @return Closure(string):array<string> The parts.
 */
function splitByLength(int $length): Closure
{
    /**
     * @param string $string The string to be split
     * @return array<int, string>
     */
    return function (string $string) use ($length): array {
        return \str_split($string, max(1, $length)) ?: array(); // @phpstan-ignore-line, inconsistent errors with differing php versions.
    };
}


/**
 * Returns a callback for splitting a string into chunks.
 *
 * @param int $length The length of each chunk.
 * @param string $end The string to use at the end.
 * @return Closure(string):string
 */
function chunk(int $length, string $end = "\r\n"): Closure
{
    /**
     * @param string $string The string to be chunked
     * @return string
     */
    return function (string $string) use ($length, $end): string {
        return \chunk_split($string, max(1, $length), $end);
    };
}

/**
 * Creates a callable for wrapping a string to a defined value.
 *
 * @param int $width Max width for each "line"
 * @param string $break The string to use to denote the end of line.
 * @param bool $cut If set to true, words are cut at $width, else overflow.
 * @return Closure(string):string
 */
function wordWrap(int $width, string $break = "\n", bool $cut = false): Closure
{
    /**
     * @param string $string The string to be wrapped
     * @return string
     */
    return function (string $string) use ($width, $break, $cut): string {
        return \wordwrap($string, $width, $break, $cut);
    };
}

/**
 * Returns a callback for counting the number of occurrences of each char in a string.
 *
 * @link https://www.php.net/manual/en/function.count-chars.php
 * @param int $mode See the PHP docs for details.
 * @return Closure(string):(int[]|string)
 */
function countChars(int $mode = 1): Closure
{
    // Throw an exception if the mode is not supported.
    if (! in_array($mode, array( 0, 1, 2, 3, 4 ), true)) {
        throw new \InvalidArgumentException('Invalid mode');
    }

    /**
     * @param string $string The string to have its char counted.
     * @return int[]|string
     */
    return function (string $string) use ($mode) {
        return \count_chars($string, $mode);
    };
}

/**
 * Returns a callable that counts the occurrences of a given substring in a string
 *
 * @param string $needle The substring to find
 * @param int $offset Place to start, defaults to 0 (start)
 * @param int|null $length Max length after offset to search.
 * @return Closure(string):int
 */
function countSubString(string $needle, int $offset = 0, ?int $length = null): Closure
{
    /**
     * @param string $haystack
     * @return int
     */
    return function (string $haystack) use ($needle, $offset, $length): int {
        return $length
            ? \substr_count($haystack, $needle, $offset, $length)
            : \substr_count($haystack, $needle, $offset);
    };
}

/**
 * Returns a callable for doing repeated trim.
 *
 * @param string $mask
 * @return Closure(string):string
 */
function trim(string $mask = "\t\n\r\0\x0B"): Closure
{
    /**
     * @param string $string The string to be trimmed
     * @return string
     */
    return function (string $string) use ($mask): string {
        return \trim($string, $mask);
    };
}

/**
 * Returns a callable for doing repeated ltrim.
 *
 * @param string $mask
 * @return Closure(string):string
 */
function lTrim(string $mask = "\t\n\r\0\x0B"): Closure
{
    /**
     * @param string $string The string to be trimmed
     * @return string
     */
    return function (string $string) use ($mask): string {
        return \ltrim($string, $mask);
    };
}

/**
 * Returns a callable for doing repeated rtrim.
 *
 * @param string $mask
 * @return Closure(string):string
 */
function rTrim(string $mask = "\t\n\r\0\x0B"): Closure
{
    /**
     * @param string $string The string to be trimmed
     * @return string
     */
    return function (string $string) use ($mask): string {
        return \rtrim($string, $mask);
    };
}

/**
 * Returns a callable for finding the similarities between 2 string.
 * This sets the defined value as the base (similar_text as first)
 *
 * @param string $base The string to act as the base.
 * @param bool $asPc If set to true will return the percentage match, rather than char count.
 * @return Closure(string):Number
 */
function similarAsBase(string $base, bool $asPc = false): Closure
{
    /**
     * @param string $comparisonString The string to compare against base.
     * @return Number
     */
    return function (string $comparisonString) use ($base, $asPc) {
        $pc       = 0.00;
        $matching = similar_text($base, $comparisonString, $pc);
        return $asPc ? $pc : $matching;
    };
}

/**
 * Returns a callable for finding the similarities between 2 string.
 * This sets the defined value as the comparisonString (similar_text as second)
 *
 * @param string $comparisonString The string to compare against base.
 * @param bool $asPc If set to true will return the percentage match, rather than char count.
 * @return Closure(string):Number
 */
function similarAsComparison(string $comparisonString, bool $asPc = false): Closure
{
    /**
     * @param string $comparisonString The string to act as the base.
     * @return Number
     */
    return function (string $base) use ($comparisonString, $asPc) {
        $pc       = 0.00;
        $matching = similar_text($base, $comparisonString, $pc);
        return $asPc ? $pc : $matching;
    };
}

/**
 * Returns a callable for padding out a string.
 *
 * @param int $length Max length to make string.
 * @param string $padContent The value to padd the string with (defaults to ' ')
 * @param int $type How to pad, please use these constants. STR_PAD_RIGHT|STR_PAD_LEFT|STR_PAD_BOTH
 * @return Closure(string):string
 */
function pad(int $length, string $padContent = ' ', int $type = STR_PAD_RIGHT): Closure
{
    /**
     * @param string $string The string to pad out.
     * @return string
     */
    return function (string $string) use ($length, $padContent, $type): string {
        return \str_pad($string, $length, $padContent, $type);
    };
}

/**
 * Returns a callable for repeating a string by a defined number of times.
 *
 * @param int $count Number of times to repeat string.
 * @return Closure(string):string
 */
function repeat(int $count): Closure
{
    /**
     * @param string $string The string to repeat
     * @return string
     */
    return function (string $string) use ($count): string {
        return \str_repeat($string, $count);
    };
}

/**
 * Returns a callback for creating a word counter, with set format and char list.
 *
 * @param int $format can use WORD_COUNT_NUMBER_OF_WORDS | WORD_COUNT_ARRAY | WORD_COUNT_ASSOCIATIVE_ARRAY
 * @param string|null $charList The char list of option values considered words.
 * @return Closure(string):(int|string[])
 */
function wordCount(int $format = WORD_COUNT_NUMBER_OF_WORDS, ?string $charList = null): Closure
{
    /**
     * @param string $string The string to pad out.
     * @return int|string[]
     */
    return function (string $string) use ($format, $charList) {
        return $charList
            ? (\str_word_count($string, $format, $charList) ?: 0)
            : (\str_word_count($string, $format) ?: 0);
    };
}

/**
 * Creates a function for stripping tags with a defined set of allowed tags.
 *
 * @param string|null $allowedTags The allowed tags, pass null or leave blank for none.
 * @return Closure(string):string
 */
function stripTags(?string $allowedTags = null): Closure
{
    /**
     * @param string $string The string to strip tags from.
     * @return string
     */
    return function (string $string) use ($allowedTags): string {
        return $allowedTags
            ? \strip_tags($string, $allowedTags)
            : \strip_tags($string);
    };
}

/**
 * Returns a callable for finding the first postition of a defined value in any string.
 *
 * @param string $needle The value to look for.
 * @param int  $offset The offset to start
 * @param int $flags STRINGS_CASE_SENSITIVE | STRINGS_CASE_INSENSITIVE
 * @return Closure(string):?int
 */
function firstPosition(string $needle, int $offset = 0, int $flags = STRINGS_CASE_SENSITIVE): Closure
{
    $caseSensitive = ! (bool) ($flags & STRINGS_CASE_INSENSITIVE); // Assumes true unless INSESNITVE passed

    /**
     * @param string $haystack The haystack to look through.
     * @return int|null
     */
    return function (string $haystack) use ($needle, $offset, $caseSensitive): ?int {
        $pos = $caseSensitive
            ? strpos($haystack, $needle, $offset)
            : stripos($haystack, $needle, $offset);
        return is_int($pos) ? (int) $pos : null;
    };
}

/**
 * Returns a callable for finding the first position of a defined value in any string.
 *
 * @param string $needle The value to look for.
 * @param int  $offset The offset to start
 * @param int $flags STRINGS_CASE_SENSITIVE | STRINGS_CASE_INSENSITIVE
 * @return Closure(string):?int
 */
function lastPosition(string $needle, int $offset = 0, int $flags = STRINGS_CASE_SENSITIVE): Closure
{
    $caseSensitive = ! (bool) ($flags & STRINGS_CASE_INSENSITIVE); // Assumes true unless INSESNITVE passed

    /**
     * @param string $haystack The haystack to look through.
     * @return int|null
     */
    return function (string $haystack) use ($needle, $offset, $caseSensitive): ?int {
        $pos = $caseSensitive
            ? strrpos($haystack, $needle, $offset)
            : strripos($haystack, $needle, $offset);
        return is_int($pos) ? (int) $pos : null;
    };
}

/**
 * Returns a callable for looking for the first occurrence of a substring.
 * When found can return all after or before the needle (sub string)
 * Can be done as case sensitive (strstr()) or insensitive (stristr())
 *
 * @param string $needle The substring to look for.
 * @param int $flags Possible STRINGS_CASE_INSENSITIVE | STRINGS_CASE_SENSITIVE | STRINGS_AFTER_NEEDLE | STRINGS_BEFORE_NEEDLE
 * @return Closure(string):string
 */
function firstSubString(string $needle, int $flags = STRINGS_CASE_SENSITIVE | STRINGS_AFTER_NEEDLE): Closure
{
    // Decode flags, only look for none defaults.
    $beforeNeedle  = (bool) ($flags & STRINGS_BEFORE_NEEDLE);
    $caseSensitive = ! (bool) ($flags & STRINGS_CASE_INSENSITIVE); // Assumes true unless INSESNITVE passed

    /**
     * @param string $haystack The haystack to look through.
     * @return string
     */
    return function (string $haystack) use ($needle, $beforeNeedle, $caseSensitive): string {
        $result = $caseSensitive
            ? strstr($haystack, $needle, $beforeNeedle)
            : stristr($haystack, $needle, $beforeNeedle);
        return is_string($result) ? $result : '';
    };
}

/**
 * Returns a callable for creating a function which finds the first occurrence of
 * any character (from a list) in a defined string.
 *
 * @param string $chars All chars to check with.
 * @return Closure(string):string
 */
function firstChar(string $chars): Closure
{
    /**
     * @param string $haystack
     * @return string
     */
    return function (string $haystack) use ($chars): string {
        $result = strpbrk($haystack, $chars);
        return is_string($result) ? $result : '';
    };
}

/**
 * Returns a function that finds the last char in a string and returns the following text.
 * Matches the first char passed, if more than 1 char passed, the rest are ignored.
 *
 * @param string $char
 * @return Closure(string):string
 */
function lastChar(string $char): Closure
{
    /**
     * @param string $haystack
     * @return string
     */
    return function (string $haystack) use ($char): string {
        $result = strrchr($haystack, $char);
        return is_string($result) ? $result : '';
    };
}

/**
 * Returns a callable which translates substrings from a defined dictionary.
 * Dictionary should be ['from' => 'to' ]
 *
 * @param array<string, mixed> $dictionary
 * @return Closure(string):string
 */
function translateWith(array $dictionary): Closure
{
    /**
     * @param string $haystack
     * @return string
     */
    return function (string $haystack) use ($dictionary): string {
        $result = strtr($haystack, $dictionary);
        return $result;
    };
}

/**
 * Creates a callable for a string safe function compose.
 *
 * @uses F\composeTypeSafe
 * @param callable(mixed):string ...$callable
 * @return Closure(mixed):string
 */
function composeSafeStringFunc(callable ...$callable): Closure
{
    return F\composeTypeSafe('is_string', ...$callable);
}

/**
 * Creates a callable for compiling a string.
 *
 * @param string $initial
 * @return Closure(string|null):(Closure|string)
 */
function stringCompiler(string $initial = ''): Closure
{
    /**
     * @param string|null $value
     * @return Closure|string
     */
    return function (?string $value = null) use ($initial) {
        if ($value) {
            $initial .= $value;
        }
        return $value ? stringCompiler($initial) : $initial;
    };
}

/**
 * Checks if the passe value is a string and if its length is 0.
 *
 * @param mixed $value
 * @return bool
 */
function isBlank($value): bool
{
    return is_string($value) && \mb_strlen($value) === 0;
}


/************************************************************************/
/************************************************************************/
/**                        Deprecated Functions                        **/
/************************************************************************/
/************************************************************************/


/**
 * See decimalNumber()
 * This function will be removed in later versions.
 *
 * @deprecated Use decimalNumber() instead.
 *
 * @param int $precision
 * @param string $point
 * @param string $thousands
 * @return Closure
 */
function decimialNumber($precision = 2, $point = '.', $thousands = ''): Closure
{
    trigger_error('Deprecated function called. Please use decimalNumber. This function will be removed in later versions.', E_USER_DEPRECATED);
    return decimalNumber($precision, $point, $thousands);
}

/**
 * See similarAsComparison()
 *
 * @deprecated Use similarAsComparison() instead.
 * @param string $comparisonString
 * @param bool $asPc
 * @return Closure
 */
function similarAsComparisson(string $comparisonString, bool $asPc = false): Closure
{
    trigger_error('Deprecated function called. Please use similarAsComparison. This function will be removed in later versions.', E_USER_DEPRECATED);
    return similarAsComparison($comparisonString, $asPc);
}

/**
 * See firstPosition()
 *
 * @deprecated
 * @param string $needle
 * @param int $offset
 * @param int $flags
 * @return Closure
 */
function firstPosistion(string $needle, int $offset = 0, int $flags = STRINGS_CASE_SENSITIVE): Closure
{
    trigger_error('Deprecated function called. Please use firstPosition. This function will be removed in later versions.', E_USER_DEPRECATED);
    return firstPosition($needle, $offset, $flags);
}

/**
 * See lastPosition()
 *
 * @deprecated Use lastPosition() instead.
 * @param string $needle The value to look for.
 * @param int  $offset The offset to start
 * @param int $flags STRINGS_CASE_SENSITIVE | STRINGS_CASE_INSENSITIVE
 * @return Closure
 */
function lastPosistion(string $needle, int $offset = 0, int $flags = STRINGS_CASE_SENSITIVE): Closure
{
    trigger_error('Deprecated function called. Please use lastPosition. This function will be removed in later versions.', E_USER_DEPRECATED);
    return lastPosition($needle, $offset, $flags);
}
