<?php

declare(strict_types=1);

require_once dirname(__FILE__, 2) . '/src/function-constants.php';

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Functions;

class FunctionConstantsTest extends TestCase
{
    /** @testdox It should be possible to use a constant for Comparisons\notEmpty() as a callable */
    public function testNotEmptyConstant()
    {
        // With arrays
        $this->assertTrue(call_user_func(Functions::NOT_EMPTY, array( 'test' )));
        $this->assertFalse(call_user_func(Functions::NOT_EMPTY, array()));

        // With strings
        $this->assertTrue(call_user_func(Functions::NOT_EMPTY, 'test'));
        $this->assertFalse(call_user_func(Functions::NOT_EMPTY, ''));
    }

    /** @testdox It should be possible to use a constant for Arrays\head() as a callable */
    public function testArrayHead(): void
    {
        $this->assertEquals('1', call_user_func(Functions::ARRAY_HEAD, array( '1', '2' )));
        $this->assertNull(call_user_func(Functions::ARRAY_HEAD, array()));
    }

    /** @testdox It should be possible to use a constant for Arrays\tail() as a callable */
    public function testArrayTail(): void
    {
        $this->assertEquals('2', call_user_func(Functions::ARRAY_TAIL, array( '1', '2' )));
        $this->assertNull(call_user_func(Functions::ARRAY_TAIL, array()));
    }

    /** @testdox It should be possible to use a constant for Comparisons\isTrue() as a callable */
    public function testIsTrue(): void
    {
        // Only a true should pass
        $this->assertTrue(call_user_func(Functions::IS_TRUE, true));

        $this->assertFalse(call_user_func(Functions::IS_TRUE, false));

        $this->assertFalse(call_user_func(Functions::IS_TRUE, 0));
        $this->assertFalse(call_user_func(Functions::IS_TRUE, 1));
        $this->assertFalse(call_user_func(Functions::IS_TRUE, 8));

        $this->assertFalse(call_user_func(Functions::IS_TRUE, ''));
        $this->assertFalse(call_user_func(Functions::IS_TRUE, '1'));

        $this->assertFalse(call_user_func(Functions::IS_TRUE, array()));
        $this->assertFalse(call_user_func(Functions::IS_TRUE, array( 1, 2, 3 )));

        $this->assertFalse(call_user_func(Functions::IS_TRUE, null));
    }

    /** @testdox It should be possible to use a constant for Comparisons\isFalse() as a callable */
    public function testIsFalse(): void
    {
        // Only a false should pass
        $this->assertTrue(call_user_func(Functions::IS_FALSE, false));

        $this->assertFalse(call_user_func(Functions::IS_FALSE, true));

        $this->assertFalse(call_user_func(Functions::IS_FALSE, 0));
        $this->assertFalse(call_user_func(Functions::IS_FALSE, 1));

        $this->assertFalse(call_user_func(Functions::IS_FALSE, ''));
        $this->assertFalse(call_user_func(Functions::IS_FALSE, '0'));
        $this->assertFalse(call_user_func(Functions::IS_FALSE, '1'));

        $this->assertFalse(call_user_func(Functions::IS_FALSE, array()));

        $this->assertFalse(call_user_func(Functions::IS_FALSE, null));
    }

    /** @testdox It should be possible to use a constant for Comparisons\isNumber() as a callable */
    public function testIsNumber(): void
    {
        // Only a number should pass
        $this->assertTrue(call_user_func(Functions::IS_NUMBER, 1));
        $this->assertTrue(call_user_func(Functions::IS_NUMBER, 1.0));
        $this->assertTrue(call_user_func(Functions::IS_NUMBER, 1.1));
        $this->assertTrue(call_user_func(Functions::IS_NUMBER, 1.2));
        $this->assertTrue(call_user_func(Functions::IS_NUMBER, 1.3));

        $this->assertFalse(call_user_func(Functions::IS_NUMBER, '1'));
        $this->assertFalse(call_user_func(Functions::IS_NUMBER, '1.0'));
        $this->assertFalse(call_user_func(Functions::IS_NUMBER, '1.1'));

        $this->assertFalse(call_user_func(Functions::IS_NUMBER, array()));
        $this->assertFalse(call_user_func(Functions::IS_NUMBER, array( 1, 2, 3 )));

        $this->assertFalse(call_user_func(Functions::IS_NUMBER, null));
    }

    /** @testdox It should be possible to use a constant for Strings\isBlank() as a callable */
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

        $this->assertFalse(call_user_func(Functions::IS_BLANK, true));
        $this->assertFalse(call_user_func(Functions::IS_BLANK, false));
    }
}
