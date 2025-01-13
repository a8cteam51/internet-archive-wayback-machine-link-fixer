<?php

/**
 * Tests for all number types
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 0.1.0
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\FunctionsLoader;

class NumberFunctionTest extends TestCase
{
    public function setup(): void
    {
        FunctionsLoader::include();
    }

    public function testCanAccumulateInteger(): void
    {
        $acc = Num\accumulatorInt(0);
        $acc = $acc(11);
        $this->assertEquals(11, $acc());
        $acc = $acc(22);
        $this->assertEquals(33, $acc());
        $acc = $acc(33);
        $this->assertEquals(66, $acc());
    }

    public function testAccumlatorIntThrowsErrorForNoneIntTypes()
    {
        $this->expectException(TypeError::class);
        $acc = Num\accumulatorInt(0);
        $acc = $acc('11');
    }

    public function testCanAccumulate(): void
    {
        $acc = Num\accumulatorFloat(0);
        $acc = $acc(0.5);
        $this->assertEquals(0.5, $acc());
        $acc = $acc(22.2);
        $this->assertEquals(22.7, $acc());
        $acc = $acc(7.3);
        $this->assertEquals(30, $acc());
    }

    public function testAccumlatorFloatThrowsErrorForNoneFloatTypes()
    {
        $this->expectException(TypeError::class);
        $acc = Num\accumulatorFloat(0);
        $acc = $acc(array(1, 2, 3, 4, 5, 6));
        // Throws InvalidArgumentException.
    }

    public function testCanSum()
    {
        $addsFive        = Num\sum(5);
        $addsTwoAndAHalf = Num\sum(2.5);

        $a = 0;
        $b = 1.00;

        $a = $addsFive($a);
        $b = $addsTwoAndAHalf($b);

        $this->assertEquals(5, $a);
        $this->assertEquals(3.5, $b);

        $this->assertEquals(10, $addsFive($a));
        $this->assertEquals(6, $addsTwoAndAHalf($b));
    }

    public function testSumThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\sum('0');
        // Throws InvalidArgumentException.
    }

    public function testCanSub()
    {
        $subsFive        = Num\subtract(5);
        $subsTwoAndAHalf = Num\subtract(2.5);

        $a = 10;
        $b = 7.5;

        $a = $subsFive($a);
        $b = $subsTwoAndAHalf($b);

        $this->assertEquals(5, $a);
        $this->assertEquals(5.0, $b);

        $this->assertEquals(0, $subsFive($a));
        $this->assertEquals(2.5, $subsTwoAndAHalf($b));
    }

    public function testSubThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\subtract('0');
        // Throws InvalidArgumentException.
    }

    public function testCanMultiply()
    {
        $subsFive        = Num\multiply(5);
        $subsTwoAndAHalf = Num\multiply(2.5);

        $a = 1;
        $b = 1;

        $a = $subsFive($a);
        $b = $subsTwoAndAHalf($b);

        $this->assertEquals(5, $a);
        $this->assertEquals(2.5, $b);

        $this->assertEquals(25, $subsFive($a));
        $this->assertEquals(6.25, $subsTwoAndAHalf($b));
    }


    public function testMultiplyThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\multiply(array(array('0'), false));
        // Throws InvalidArgumentException.
    }

    public function testCanDivideByAndInto()
    {
        $divideBy2   = Num\divideBy(2);
        $divideInto2 = Num\divideInto(2);

        $a = 10;
        $b = 10;

        $a = $divideBy2($a);
        $b = $divideInto2($b);

        $this->assertEquals(5, $a);
        $this->assertEquals(0.2, $b);

        $this->assertEquals(2.5, $divideBy2($a));
        $this->assertEquals(10, $divideInto2($b));
    }

    /** @testdox Attempting to pass anything but a numerical value as the dividend to divideBy should throw an InvalidArgumentException */
    public function testDivideByThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\divideBy(array(array('0'), false));
        // Throws InvalidArgumentException.
    }

    /** @testdox Attempting to pass anything but a numerical value as the dividend to divideInto should throw an InvalidArgumentException */
    public function testDivideIntoThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\divideInto(array(array('0'), false));
        // Throws InvalidArgumentException.
    }

    public function testCanRemainderByAndInto()
    {
        $remainderBy2 = Num\remainderBy(2);
        $this->assertEquals(0, $remainderBy2(10)); // 10 / 2 = 5
        $this->assertEquals(1, $remainderBy2(9)); // 9 / 2 = 4.5

        $remainderInto2 = Num\remainderInto(2);
        $this->assertEquals(2, $remainderInto2(10)); // 2 / 10 = 0.2
    }

    /** @testdox Attempting to pass anything but a numerical value as the dividend to remainderInto should throw an InvalidArgumentException */
    public function testRemainderIntoThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\remainderInto(array(array('0'), false));
        // Throws InvalidArgumentException.
    }

    public function testRemainderByThrowsNoneNumError()
    {
        $this->expectException(InvalidArgumentException::class);
        $acc = Num\remainderBy(array(array('0'), false));
        // Throws InvalidArgumentException.
    }

    public function testCanRoundFloatsAndInts()
    {
        $twoDecimalPlaces   = Num\round(2);
        $eightDecimalPlaces = Num\round(8);

        $this->assertEquals(8.12, $twoDecimalPlaces(8.123456));
        $this->assertEquals(8.12345678, $eightDecimalPlaces(8.123456781));
        $this->assertEquals(50, $eightDecimalPlaces(50));
        $this->assertEquals(1, $twoDecimalPlaces(1));
    }


    public function testRoundThrowsNoneNumErrorA()
    {
        $this->expectException(InvalidArgumentException::class);
        $rounder = Num\round(array('HELLO', 'NOT A NUMBER'));
        // Throws InvalidArgumentException.
    }


    public function testRoundThrowsNoneNumErrorB()
    {
        $this->expectException(InvalidArgumentException::class);
        $rounder = Num\round(5);
        $rounder('STRINGS');
        // Throws InvalidArgumentException.
    }

    /** @testdox It should be possible to check if a number is a factor of another number */
    public function testisMultipleOf(): void
    {
        $factorOf5 = Num\isMultipleOf(5);

        // Is mutliples of 5
        $this->assertTrue($factorOf5(10));
        $this->assertTrue($factorOf5(15));
        $this->assertTrue($factorOf5(20));

        // Is not mutliples of 5
        $this->assertFalse($factorOf5(4));
        $this->assertFalse($factorOf5(6));
        $this->assertFalse($factorOf5(7));
        $this->assertFalse($factorOf5(0));
        $this->assertFalse($factorOf5(-1));
    }

    /** @testdox Attempting to use a none number (int or float) as the value for factor, should throw an error */
    public function testIsMultipleOfThrowsIfBaseNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        Num\isMultipleOf('5');
    }

    /** @testdox Attempting to use a none number (int or float) as the value for checked number, should throw an error */
    public function testIsMultipleOfThrowsIfCompNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        $r = Num\isMultipleOf(5);
        $r('HELLO');
    }

    /** @testdox It Should be possible to raise a number by a pre defined exponent as a closure */
    public function testPowerOf(): void
    {
        // [exponent, value, expected]
        $data = [
            [2, 2, 4],
            [3, 3, 27],
            [5, 11, 161051],
            [3, -3, -27],
        ];

        foreach ($data as list($exponent, $value, $expected)) {
            $this->assertEquals($expected, Num\power($exponent)($value));
        }
    }

    /** @testdox Attempting to use a none number (int or float) as the value for factor, should throw an error */
    public function testPowerOfThrowsIfBaseNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        Num\power('5');
    }

    /** @testdox Attempting to use a none number (int or float) as the value passed to be raised to the power of, should throw an error */
    public function testPowerOfThrowsIfCompNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        $r = Num\power(5);
        $r('HELLO');
    }

    /** @testdox It Should be possible to raise a number by a pre defined exponent as a closure */
    public function testRoot(): void
    {
        // [root, value, expected]
        $data = [
            [2, 4, 2.0],
            [3, 27, 3.0],
            [3, 4, 1.5874010519682],
            [-13, 12, 0.8260114319958],
        ];

        foreach ($data as list($root, $value, $expected)) {
            $this->assertEquals(round($expected, 12), round(Num\root($root)($value), 12));
        }
    }

    /** @testdox Attempting to use a none number (int or float) as the value for root, should throw an error */
    public function testRootThrowsIfBaseNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        Num\root('5');
    }

    /** @testdox Attempting to use a none number (int or float) as the value passed to be raised to the root should throw an error */
    public function testRootThrowsIfCompNotNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        $r = Num\root(5);
        $r('HELLO');
    }

    /** @testdox It should be possible to check if a number is a factor of another number */
    public function testisFactorOf(): void
    {
        $factorOf27 = Num\isFactorOf(27);

        // Is factors of 27
        $this->assertTrue($factorOf27(1));
        $this->assertTrue($factorOf27(3));
        $this->assertTrue($factorOf27(9));
        $this->assertTrue($factorOf27(-1));

        // Is not factors of 27
        $this->assertFalse($factorOf27(4));
        $this->assertFalse($factorOf27(6));
        $this->assertFalse($factorOf27(7));
        $this->assertFalse($factorOf27(0));
    }
}
