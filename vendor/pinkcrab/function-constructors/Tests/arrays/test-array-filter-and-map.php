<?php

declare(strict_types=1);

require_once dirname(__FILE__, 3) . '/FunctionsLoader.php';

/**
 * Tests for all array filter based functions.
 *
 * @since 0.1.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 */
use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;
use PinkCrab\FunctionConstructors\GeneralFunctions as Func;

/**
 * ArrayFunction class.
 */
class ArrayFilterAndMapTests extends TestCase
{
    public function setup(): void
    {
        FunctionsLoader::include();
    }

    public function testCanUseFilter()
    {
        $names = array( 'James Smith', 'Betty Jones', 'Sam Power', 'Rebecca Smith' );

        $isASmith = Arr\filter(Str\contains('Smith'));

        $this->assertContains('James Smith', $isASmith($names));
        $this->assertContains('Rebecca Smith', $isASmith($names));
        $this->assertNotContains('Betty Jones', $isASmith($names));
        $this->assertNotContains('Sam Power', $isASmith($names));
    }

    public function testCanUseFilterKey()
    {
        $names = array(
            'name10' => 'James Smith',
            'name20' => 'Betty Jones',
            'name21' => 'Sam Power',
            'name38' => 'Rebecca Smith',
        );

        $isASmith = Arr\filterKey(Str\contains('name2'));

        $this->assertNotContains('James Smith', $isASmith($names));
        $this->assertNotContains('Rebecca Smith', $isASmith($names));
        $this->assertContains('Betty Jones', $isASmith($names));
        $this->assertContains('Sam Power', $isASmith($names));
    }

    public function testCanFilterOr()
    {
        $names = array( 'James Smith', 'Betty Jones', 'Sam Power', 'Rebecca Smith' );

        $isSmithOrJones = Arr\filterOr(
            Str\contains('Smith'),
            Str\contains('Jones')
        );

        $this->assertContains('James Smith', $isSmithOrJones($names));
        $this->assertContains('Rebecca Smith', $isSmithOrJones($names));
        $this->assertContains('Betty Jones', $isSmithOrJones($names));
        $this->assertNotContains('Sam Power', $isSmithOrJones($names));
    }

    public function testCanFilterAnd()
    {
        $names = array( 'James Smith', 'Betty Jones', 'Sam Power', 'Rebecca Smith' );

        $isSamAndPower = Arr\filterAnd(
            Str\contains('Power'),
            Str\contains('Sam')
        );

        $this->assertNotContains('James Smith', $isSamAndPower($names));
        $this->assertNotContains('Rebecca Smith', $isSamAndPower($names));
        $this->assertNotContains('Betty Jones', $isSamAndPower($names));
        $this->assertContains('Sam Power', $isSamAndPower($names));
    }

    public function testCanFilterLast(): void
    {
        $lastEven = Arr\filterLast(
            function ($e) {
                return $e % 2 === 0;
            }
        );
        $this->assertEquals(6, $lastEven(array( 1, 3, 4, 6 )));
        $this->assertEquals(8, $lastEven(array( 1, 3, 5, 7, 9, 6, 8 )));
        $this->assertNull($lastEven(array( 1, 3, 3 )));
    }

    public function testCanFilterFirst(): void
    {
        $firstEven = Arr\filterFirst(
            function ($e) {
                return $e % 2 === 0;
            }
        );
        $this->assertEquals(6, $firstEven(array( 1, 3, 6 )));
        $this->assertEquals(8, $firstEven(array( 1, 3, 5, 7, 9, 8 )));
        $this->assertNull($firstEven(array( 1, 3, 3 )));
    }

    public function testCanFilterMap()
    {
        $nums               = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $doubledEvenNumbers = Arr\filterMap(
            function ($a) {
                // is even
                return $a % 2 === 0;
            },
            function ($b) {
                // double it
                return $b * 2;
            }
        );

        $this->assertEquals(4, $doubledEvenNumbers($nums)[1]);
        $this->assertEquals(8, $doubledEvenNumbers($nums)[3]);
        $this->assertEquals(12, $doubledEvenNumbers($nums)[5]);
        $this->assertEquals(16, $doubledEvenNumbers($nums)[7]);
        $this->assertEquals(20, $doubledEvenNumbers($nums)[9]);

        $this->assertArrayNotHasKey(0, $doubledEvenNumbers($nums));
        $this->assertArrayNotHasKey(2, $doubledEvenNumbers($nums));
        $this->assertArrayNotHasKey(4, $doubledEvenNumbers($nums));
        $this->assertArrayNotHasKey(6, $doubledEvenNumbers($nums));
        $this->assertArrayNotHasKey(8, $doubledEvenNumbers($nums));
    }

    /** @testdox It should be possible to check if all values of an array pass a filter */
    public function testCanFilterAll()
    {
        $nums = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $this->assertTrue(Arr\filterAll('is_numeric')($nums));
        $this->assertFalse(
            Arr\filterAll(
                function ($a) {
                    return $a !== 2;
                }
            )($nums)
        );
    }

    /** @testdox It should be possible to check if any value of an array passes a filter */
    public function testCanFilterAny()
    {
        $nums = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $this->assertTrue(Arr\filterAny('is_numeric')($nums));
        $this->assertTrue(
            Arr\filterAny(
                function ($a) {
                    return $a === 2;
                }
            )($nums)
        );
        $this->assertFalse(
            Arr\filterAny(
                function ($a) {
                    return $a === 20;
                }
            )($nums)
        );
    }

    public function testCanMapArrayKeys(): void
    {
        $origArray = array(
            'a' => 'aa',
            'b' => 'bb',
            'c' => 'cc',
        );

        $capitaliseKeys = Arr\mapKey('strtoupper');
        $capArray       = $capitaliseKeys($origArray);
        $this->assertArrayHasKey('A', $capArray);
        $this->assertArrayNotHasKey('a', $capArray);
        // Check inital array the same.
        $this->assertArrayHasKey('a', $origArray);
        $this->assertArrayNotHasKey('A', $origArray);
    }

    public function testCanMapWithData(): void
    {
        $array         = range(1, 10);
        $mapWithCheese = Arr\mapWith(
            function ($e, $cheese) {
                return "$e loves $cheese";
            },
            'CHEESE!!!!!'
        );
        $this->assertEquals('3 loves CHEESE!!!!!', $mapWithCheese($array)[2]);
        $this->assertEquals('6 loves CHEESE!!!!!', $mapWithCheese($array)[5]);
        $this->assertEquals('9 loves CHEESE!!!!!', $mapWithCheese($array)[8]);

        $mapWithMORECheese = Arr\mapWith(
            function ($e, $cheese, $more) {
                return "$e loves $cheese.......$more";
            },
            'CHEESE!!!!!',
            'YOU HEAR ME!'
        );
        $this->assertEquals('3 loves CHEESE!!!!!.......YOU HEAR ME!', $mapWithMORECheese($array)[2]);
        $this->assertEquals('6 loves CHEESE!!!!!.......YOU HEAR ME!', $mapWithMORECheese($array)[5]);
        $this->assertEquals('9 loves CHEESE!!!!!.......YOU HEAR ME!', $mapWithMORECheese($array)[8]);
    }

    public function testCanMapArray(): void
    {
        $origArray = array(
            'a' => 'aa',
            'b' => 'bb',
            'c' => 'cc',
        );

        $capitaliseVals = Arr\map('strtoupper');
        $capArray       = $capitaliseVals($origArray);
        $this->assertEquals('AA', $capArray['a']);
        $this->assertNotEquals('aa', $capArray['a']);
        // Check inital array the same.
        $this->assertEquals('aa', $origArray['a']);
        $this->assertNotEquals('AA', $origArray['a']);
    }

    public function testCanUseFlatMap(): void
    {
        $array = array(
            0,
            1,
            2,
            array( 3, 4 ),
            array(
                array(),
                5,
                6,
                7,
                8,
                array(
                    9,
                    10,
                    array( 11, 12, 13 ),
                ),
            ),
        );

        $doubleNFlattenIt = Arr\flatMap(
            function ($e) {
                return $e * 2;
            }
        );
        $this->assertEquals(24, $doubleNFlattenIt($array)[12]);
        $this->assertEquals(20, $doubleNFlattenIt($array)[10]);
        $this->assertEquals(10, $doubleNFlattenIt($array)[5]);
        $this->assertEquals(16, $doubleNFlattenIt($array)[8]);
        $this->assertEquals(4, $doubleNFlattenIt($array)[2]);
    }
}
