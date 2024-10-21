<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Functions;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;

/**
 * StringFunction class.
 */
class StringFunctionTest extends TestCase
{
    public function setup(): void
    {
        FunctionsLoader::include();
    }


    public function testCanWrapString(): void
    {
        $foo = Str\wrap('--', '++');
        $this->assertEquals('--HI++', $foo('HI'));
        $this->assertEquals('--123++', $foo('123'));

        $bar = Str\wrap('\/');
        $this->assertEquals('\/HI\/', $bar('HI'));
        $this->assertEquals('\/123\/', $bar('123'));
    }

    /** @testdox It should be possible to split a string by defining a start point with an optional length. */
    public function testCanSliceString(): void
    {
        $first5 = Str\slice(0, 5);
        $this->assertEquals('Hello', $first5('Hello World'));
        $this->assertEquals('He', $first5('He'));

        $skip5 = Str\slice(5);
        $this->assertEquals('WorldBar', $skip5('HelloWorldBar'));
    }

    public function testCanPrependString(): void
    {
        $prep10 = Str\prepend('10');
        $this->assertEquals('10HI', $prep10('HI'));
        $this->assertEquals('1077', $prep10('77'));
    }

    public function testCanAppendString(): void
    {
        $append10 = Str\append('10');
        $this->assertEquals('HI10', $append10('HI'));
        $this->assertEquals('7710', $append10('77'));
    }

    public function testCanCurryReplace(): void
    {
        $find_to_mask = Str\findToReplace('to mask');

        // Mask with XX
        $maskWithXX = $find_to_mask('xx');
        $string     = 'This has some test to mask and some more to mask';
        $this->assertEquals(
            'This has some test xx and some more xx',
            $maskWithXX($string)
        );

        // Mask with YY
        $maskWithYY = $find_to_mask('yy');
        $string     = 'This has some test to mask and some more to mask';
        $this->assertEquals(
            'This has some test yy and some more yy',
            $maskWithYY($string)
        );

        // Inlined.
        $this->assertEquals(
            'This has some test xx and some more xx',
            Str\findToReplace('to mask')('xx')($string)
        );
    }

    public function testCanReplaceInString(): void
    {
        $replaceGlynnWithHa = Str\replaceWith('glynn', 'ha');
        $this->assertEquals('Hi ha', $replaceGlynnWithHa('Hi glynn'));
        $this->assertEquals('ha ha ha', $replaceGlynnWithHa('glynn glynn glynn'));
    }

    public function testCanReplaceSubStrings()
    {
        $atStart       = Str\replaceSubString('PHP');
        $startAt10     = Str\replaceSubString('PHP', 10);
        $startAt10For5 = Str\replaceSubString('PHP', 10, 5);

        $string = 'abcdefghijklmnopqrstuvwxyz';

        $this->assertEquals('PHP', $atStart($string));
        $this->assertEquals('abcdefghijPHP', $startAt10($string));
        $this->assertEquals('abcdefghijPHPpqrstuvwxyz', $startAt10For5($string));
    }

    public function testStringContains(): void
    {
        $contains = Str\contains('--');
        $this->assertTrue($contains('--True'));
        $this->assertFalse($contains('++False'));
    }

    public function testStringStartWith(): void
    {
        $startsWithA = Str\startsWith('--');
        $this->assertTrue($startsWithA('--True'));
        $this->assertFalse($startsWithA('++False'));
    }

    public function testStringEndWith(): void
    {
        $endsWith = Str\endsWith('--');
        $this->assertTrue($endsWith('--True--'));
        $this->assertFalse($endsWith('++False++'));
    }

    public function testCanDoContainsPattern()
    {
        $hasFooWithoutPreceedingBar = Str\containsPattern('/(?!.*bar)(?=.*foo)^(\w+)$/');

        $this->assertTrue($hasFooWithoutPreceedingBar('blahfooblah'));
        $this->assertTrue($hasFooWithoutPreceedingBar('somethingfoo'));
        $this->assertFalse($hasFooWithoutPreceedingBar('blahfooblahbarfail'));
        $this->assertFalse($hasFooWithoutPreceedingBar('shouldbarfooshouldfail'));
        $this->assertFalse($hasFooWithoutPreceedingBar('barfoofail'));
    }

    public function testCanSplitStringWithPattern()
    {
        $splitter = Str\splitPattern('/-/');
        $date1    = '1970-01-01';
        $date2    = '2020-11-11';
        $dateFail = 'RETURNED1';

        $this->assertEquals('1970', $splitter($date1)[0]);
        $this->assertEquals('01', $splitter($date1)[1]);
        $this->assertEquals('01', $splitter($date1)[2]);

        $this->assertEquals('2020', $splitter($date2)[0]);
        $this->assertEquals('11', $splitter($date2)[1]);
        $this->assertEquals('11', $splitter($date2)[2]);

        $this->assertEquals('RETURNED1', $splitter($dateFail)[0]);
    }

    public function testCanFormatDecimalNumber()
    {
        $eightDecimalPlaces  = Str\decimalNumber(8);
        $tenDecimalPlaces    = Str\decimalNumber(10);
        $doubleDecimalPlaces = Str\decimalNumber(2);

        $this->assertEquals('3.50000000', $eightDecimalPlaces(3.5));
        $this->assertEquals('2.6580000000', $tenDecimalPlaces('2.658'));
        $this->assertEquals('3.14', $doubleDecimalPlaces(M_PI));

        // With thousand separator
        $withPipe = Str\decimalNumber(2, '.', '|');
        $this->assertEquals('123|456|789.12', $withPipe(123456789.123456));
    }

    public function testCanStripCSlashes()
    {
        $escA_C_U = Str\addSlashes('ACU');

        $this->assertEquals('\Abcd\Cfjruuuu\U', $escA_C_U('AbcdCfjruuuuU'));
    }

    public function testCanComposeWithSafeStrings(): void
    {
        $reutrnsArray = function ($e) {
            return array();
        };

        $function = Str\composeSafeStringFunc(
            Str\replaceWith('3344', '*\/*'),
            Str\replaceWith('5566', '=/\='),
            $reutrnsArray,
            Str\prepend('00'),
            Str\append('99')
        );
        $this->assertNull($function('1122334455667788'));
    }

    public function testStringCompilerCanBeUsedAsAJournal(): void
    {
        $journal = Str\stringCompiler('');
        $journal = $journal('11');
        $this->assertEquals('11', $journal());
        $journal = $journal('22');
        $this->assertEquals('1122', $journal());
        $journal = $journal('33');
        $this->assertEquals('112233', $journal());
    }

    public function testComposedWithArrayMap(): void
    {
        $function = Str\composeSafeStringFunc(
            Str\replaceWith('a', '_a_'),
            Str\replaceWith('t', '-t-'),
            Str\prepend('00'),
            Str\append('99')
        );

        $results = array_map($function, array( '1a2', '1b3', '1t4' ));
        $this->assertEquals('001_a_299', $results[0]);
        $this->assertEquals('001b399', $results[1]);
        $this->assertEquals('001-t-499', $results[2]);
    }

    public function testCanAddSlashes()
    {
        $slashA = Str\addSlashes('a');
        $this->assertEquals('h\appy d\ays', $slashA('happy days'));

        $slashAD = Str\addSlashes('ad');
        $this->assertEquals('h\appy \d\ays', $slashAD('happy days'));
    }

    public function testCanSplitStringByLength()
    {
        $splitIntoFours = Str\splitByLength(4);

        $split = $splitIntoFours('AAAABBBBCCCCDDDD');
        $this->assertEquals('AAAA', $split[0]);
        $this->assertEquals('BBBB', $split[1]);
        $this->assertEquals('CCCC', $split[2]);
        $this->assertEquals('DDDD', $split[3]);
    }

    /** @testdox It should be possible to split a string into an array using a define separator and using the limit */
    public function testCanSplitStringBySeperatorAndLimit()
    {
        $splitByComma = Str\split(',', 2);

        $split = $splitByComma('AAAA,BBBB,CCCC,DDDD');
        $this->assertEquals('AAAA', $split[0]);
        $this->assertEquals('BBBB,CCCC,DDDD', $split[1]);
    }

    /** @testdox It should be possible to split a string into an array using a defined separator with an unlimited limit.  */
    public function testCanSplitStringBySeperatorAndUnlimitedLimit()
    {
        $splitByComma = Str\split(',');

        $split = $splitByComma('AAAA,BBBB,CCCC,DDDD');
        $this->assertEquals('AAAA', $split[0]);
        $this->assertEquals('BBBB', $split[1]);
        $this->assertEquals('CCCC', $split[2]);
        $this->assertEquals('DDDD', $split[3]);
    }

    public function testCanSplitChunkString()
    {
        $in5s = Str\chunk(5, '-');
        $this->assertEquals('aaaaa-bbbbb-ccccc-', $in5s('aaaaabbbbbccccc'));
    }

    public function testCanCountCharsInString()
    {
        $getOccurances = Str\countChars();
        $this->assertCount(4, $getOccurances('Hello'));
        $this->assertCount(1, $getOccurances('a'));
        $this->assertCount(8, $getOccurances('asfetwafgh'));
    }

    public function testCanwordWrap()
    {
        $loose10 = Str\wordWrap(10, '--');
        $tight5  = Str\wordWrap(5, '--', true);

        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->assertEquals('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $loose10($string));
        $this->assertEquals('ABCDE--FGHIJ--KLMNO--PQRST--UVWXY--Z', $tight5($string));

        $string = 'ABCDEF GHIJK LMN OPQ RST UV WX YZ';
        $this->assertEquals('ABCDEF--GHIJK LMN--OPQ RST UV--WX YZ', $loose10($string));
        $this->assertEquals('ABCDE--F--GHIJK--LMN--OPQ--RST--UV WX--YZ', $tight5($string));
    }

    public function testCanDoTrim()
    {
        $trimZ = Str\trim('zZ');
        $this->assertEquals('44455', $trimZ('zzzz44455ZZZZZZZZZ'));

        $trimAB = Str\lTrim('AB');
        $this->assertEquals('STD', $trimAB('ABSTD'));
        $this->assertEquals('HFJKGHDJKGHFJKFGJKFGJK', $trimAB('ABHFJKGHDJKGHFJKFGJKFGJK'));

        $trimIU = Str\trim('IU');
        $this->assertEquals('RESSTD', $trimIU('IURESSTDIU'));
        $this->assertEquals('TREHFJKGHDJKGHFJKFGJKFGJK', $trimIU('IUIUIUTREHFJKGHDJKGHFJKFGJKFGJKIUIUIUIUIUIUIUIUIUIU'));

        $this->assertEquals('CLEAR', Str\trim()("\t\nCLEAR\r\0"));

        $trimYZ = Str\rTrim('YZ');
        $this->assertEquals('STD', $trimYZ('STDYZ'));
        $this->assertEquals('ABHFJKGHDJKGHFJKFGJKFGJK', $trimYZ('ABHFJKGHDJKGHFJKFGJKFGJKYZ'));
    }

    public function testCanDosimilarAsBase()
    {
        $compareTheBaseAsChars = Str\similarAsBase('THE BASE');
        $compareTheBaseAsPC    = Str\similarAsBase('THE BASE', true);
        $this->assertEquals(4, $compareTheBaseAsChars('BASE'));
        $this->assertEquals(round((6 / 9), 4) * 100, round($compareTheBaseAsPC('BASE'), 2));
    }

    public function testCanDosimilarAsComparisson()
    {
        $compareTheBaseAsChars = Str\similarAsComparison('BASE');
        $compareTheBaseAsPC    = Str\similarAsComparison('BASE', true);
        $this->assertEquals(4, $compareTheBaseAsChars('THE BASE'));

        // This is not the calc done in the fucntion, but give the desired answer simpler!
        $this->assertEquals(66.66666666666667, $compareTheBaseAsPC('THE BASE'));
    }

    public function testCanPadStrings()
    {
        $padLeft10  = Str\pad(10, '.', STR_PAD_LEFT);
        $padRight10 = Str\pad(10, '_', STR_PAD_RIGHT);
        $padBoth10  = Str\pad(10, '\'', STR_PAD_BOTH);

        $this->assertEquals('........HI', $padLeft10('HI'));
        $this->assertEquals('HI________', $padRight10('HI'));
        $this->assertEquals("''''HI''''", $padBoth10('HI'));
    }

    public function testCanRepeatString()
    {
        $sayItTrice = Str\repeat(3);
        $this->assertEquals('HIHIHI', $sayItTrice('HI'));
    }

    public function testCanDoWordCounts()
    {
        // Check can count words and reutrn count.
        $WordCount = Str\wordCount(WORD_COUNT_NUMBER_OF_WORDS);
        $this->assertEquals(3, $WordCount('HI HI HI'));

        // Test can return array of word counts.
        $wordList = Str\wordCount(WORD_COUNT_ARRAY)('HI BYE MAYBE');
        $this->assertEquals('HI', $wordList[0]);
        $this->assertEquals('BYE', $wordList[1]);
        $this->assertEquals('MAYBE', $wordList[2]);

        // Test can return ass array of word counts with positions.
        $wordListPositions = Str\wordCount(WORD_COUNT_ASSOCIATIVE_ARRAY)('HI BYE MAYBE');
        $this->assertEquals('HI', $wordListPositions[0]);
        $this->assertEquals('BYE', $wordListPositions[3]);
        $this->assertEquals('MAYBE', $wordListPositions[7]);

        // Count words in sub string.
        $hiCount = Str\wordCount(WORD_COUNT_NUMBER_OF_WORDS, '£')('HI BYE MAY£E');
        $this->assertEquals(3, $hiCount);
    }

    public function testCanCountSubStrings()
    {
        $findNemoAll                     = Str\countSubString('Nemo');
        $findNemoAllAfter20              = Str\countSubString('Nemo', 20);
        $findNemoAllAfter20OnlyFor40More = Str\countSubString('Nemo', 20, 40);

        $haystack1 = str_repeat('Nemo is a fish.', 11); // 15 chars
        $haystack2 = str_repeat('Nemo = Fish |', 14); // 13 chars

        $this->assertEquals(11, $findNemoAll($haystack1));
        $this->assertEquals(14, $findNemoAll($haystack2));

        $this->assertEquals(9, $findNemoAllAfter20($haystack1));
        $this->assertEquals(12, $findNemoAllAfter20($haystack2));

        $this->assertEquals(2, $findNemoAllAfter20OnlyFor40More($haystack1));
        $this->assertEquals(3, $findNemoAllAfter20OnlyFor40More($haystack2));
    }

    public function testCanStripTags()
    {
        $allTags    = Str\stripTags();
        $allowPTags = Str\stripTags('<p><a>');

        $this->assertEquals('1Stuff', $allTags('1<p>Stuff</p>'));
        $this->assertEquals('1<p>Stuff</p>', $allowPTags('1<p>Stuff</p>'));
    }

    public function testCanFindFirstPosition()
    {
        $findAppleCaseSense = Str\firstPosition('Apple');
        $this->assertEquals(0, $findAppleCaseSense('Apple are tasty'));
        $this->assertEquals(19, $findAppleCaseSense('I really dont like Apples'));
        $this->assertNull($findAppleCaseSense('APPLES ARE TASTY'));

        $findAppleCaseInsense = Str\firstPosition('ApPle', 10, STRINGS_CASE_INSENSITIVE);
        $this->assertNull($findAppleCaseInsense('Hmm yes, APPLES are really tasty'));
        $this->assertEquals(19, $findAppleCaseInsense('I really dont like APplE Tree'));
    }

    public function testCanFindLastPosition()
    {
        $findLastAppleCaseSense = Str\lastPosition('Apple');
        $this->assertEquals(13, $findLastAppleCaseSense('Apple a day, Apple are tasty'));
        $this->assertEquals(47, $findLastAppleCaseSense('I really dont like Apples but i do really like Apple skin'));
        $this->assertNull($findLastAppleCaseSense('APPLES ARE TASTY'));

        $findAppleCaseInsense = Str\lastPosition('ApPle', 0, STRINGS_CASE_INSENSITIVE);
        $this->assertEquals(25, $findAppleCaseInsense('APPLES are tasty, I like ApPleS'));
        $this->assertEquals(41, $findAppleCaseInsense('I really dont like APplE Tree, they grow ApPLES'));
    }

    public function testCanFindSubStrings()
    {
        $caseSenseBefore   = Str\firstSubString('abc', STRINGS_BEFORE_NEEDLE);
        $caseSenseAfter    = Str\firstSubString('abc');
        $caseInsenseBefore = Str\firstSubString('aBc', STRINGS_BEFORE_NEEDLE | STRINGS_CASE_INSENSITIVE);
        $caseInsenseAfter  = Str\firstSubString('abC', STRINGS_CASE_INSENSITIVE);

        $this->assertEquals('abcefg', $caseSenseAfter('qwertabcefg'));
        $this->assertEquals('qwert', $caseSenseBefore('qwertabcefg'));

        $this->assertNotNull($caseSenseAfter('rutoiuerot')); // No match
        $this->assertNotNull($caseSenseAfter('QWERTABCEFG')); // Uppercase
        $this->assertEquals('', $caseSenseAfter('rutoiuerot'));// No match
        $this->assertEquals('', $caseSenseAfter('QWERTABCEFG'));// Uppercase

        $this->assertEquals('ABCEFG', $caseInsenseAfter('QWERTABCEFG'));
        $this->assertEquals('QWERT', $caseInsenseBefore('QWERTABCEFG'));
        $this->assertEquals('abcefg', $caseInsenseAfter('qwertabcefg'));
        $this->assertEquals('qwert', $caseInsenseBefore('qwertabcefg'));
    }

    public function testCanFindfirstChar()
    {
        $findAorB = Str\firstChar('aAbB');
        $this->assertEquals('banana', $findAorB('qweiuioubanana'));
        $this->assertEquals('a12345', $findAorB('eruweyriwyriwa12345'));
        $this->assertEquals('', $findAorB('zzzzzzzzzzzzzzzzzzz'));
    }

    public function testCanFindlastChar()
    {
        $findAorB = Str\lastChar('a');
        $this->assertEquals('a6', $findAorB('a1a2a3a4a5a6'));
        $this->assertEquals('abc', $findAorB('eruweyriwyriwa12345abc'));
        $this->assertEquals('', $findAorB('zzzzzzzzzzzzzzzz'));
    }

    public function testCanTranslateSubString()
    {
        $rosStone = Str\translateWith(
            array(
                'Hi'   => 'Hello',
                'Yeah' => 'Yes',
                'Sod'  => 'XXX',
            )
        );

        $this->assertEquals('Hello you', $rosStone('Hi you'));
        $this->assertEquals('Hello Hello', $rosStone('Hi Hi'));

        $this->assertEquals('Yes we can do that', $rosStone('Yeah we can do that'));

        $this->assertEquals('XXX off', $rosStone('Sod off'));
    }

    public function testCanUseVSprintf()
    {
        $formatter = Str\vSprintf('12%s34-%s-%s-f');
        $this->assertEquals('12alpha34-12-12.5-f', $formatter(array( 'alpha', '12', '12.5' )));
    }

    /** @testdox It should be possible to check if a value is a string and is blank */
    public function testIsBlank(): void
    {
        // With string values
        $this->assertTrue(call_user_func(Functions::IS_BLANK, ''));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, 'a'));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, ' '));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, '  '));

        // With other types
        $this->assertFalse(call_user_func(Functions::IS_BLANK, array()));

        $this->assertFalse(call_user_func(Functions::IS_BLANK, null));

        $this->assertFalse(call_user_func(Functions::IS_BLANK, new \stdClass()));

        $this->assertFalse(call_user_func(Functions::IS_BLANK, 1));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, 1.0));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, 0));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, 0.0));

        $this->assertFalse(call_user_func(Functions::IS_BLANK, true));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, false));
    }

    /** @testdox Checking if a string ends with a blank string, will always return true, without checking. */
    public function testEndsWithBlank(): void
    {
        $this->assertTrue(Str\endsWith('')('fffff'));
    }

    /** @testdox Using an invalid mode for countChar should result in an InvalidArgumentexception being thrown */
    public function testCountCharInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Str\countChars(23);
    }

    /** @testdox Calling decimialNumber should throw a deprecation notice */
    public function testDecimalNumberDeprecation(): void
    {
        // If using PHPUnit 9, we need to use the expectDeprecation() method
        if (version_compare(\PHPUnit\Runner\Version::id(), '9.0.0', '>=')) {
            $this->expectDeprecation();
        } else {
            $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        }
        $result = Str\decimialNumber('2');
        $this->assertEquals('2', $result);
    }

    /** @testdox Even though decimialNumber throws a deprecation notice, it should still work. */
    public function testDecimalNumber(): void
    {
        $result = @Str\decimialNumber(2, '.', '|');
        $this->assertEquals('123|456|789.12', $result(123456789.123456));
    }

    /** @testdox Calling similarAsComparisson should throw a deprecation notice */
    public function testSimilarAsComparissonDeprecation(): void
    {
        // If using PHPUnit 9, we need to use the expectDeprecation() method
        if (version_compare(\PHPUnit\Runner\Version::id(), '9.0.0', '>=')) {
            $this->expectDeprecation();
        } else {
            $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        }
        Str\similarAsComparisson('a');
    }

    /** @testdox similarAsComparisson should still function even though it throws */
    public function testSimilarAsComparisson(): void
    {
        $compareTheBaseAsChars = @Str\similarAsComparisson('BASE');
        $this->assertEquals(4, $compareTheBaseAsChars('THE BASE'));
    }

    /** @testdox Calling firstPosistion should throw a deprecation notice */
    public function testFirstPosistionDeprecation(): void
    {
        // If using PHPUnit 9, we need to use the expectDeprecation() method
        if (version_compare(\PHPUnit\Runner\Version::id(), '9.0.0', '>=')) {
            $this->expectDeprecation();
        } else {
            $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        }
        Str\firstPosistion('a');
    }

    /** @testdox firstPosistion should still function even though it throws */
    public function testFirstPosistion(): void
    {
        $findAppleCaseSense = @Str\firstPosistion('Apple');
        $this->assertEquals(0, $findAppleCaseSense('Apple are tasty'));
    }


    /** @testdox Calling lastPosistion should throw a deprecation notice */
    public function testLastPosistionDeprecation(): void
    {
        // If using PHPUnit 9, we need to use the expectDeprecation() method
        if (version_compare(\PHPUnit\Runner\Version::id(), '9.0.0', '>=')) {
            $this->expectDeprecation();
        } else {
            $this->expectException(\PHPUnit\Framework\Error\Deprecated::class);
        }
        Str\lastPosistion('s');
    }

    /** @testdox lastPosistion should still function even though it throws */
    public function testLastPosistion(): void
    {
        $findAppleCaseSense = @Str\lastPosistion('Apple');
        $this->assertEquals(23, $findAppleCaseSense('Apple are tasty i like Apples'));
    }
}
