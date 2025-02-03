<?php

declare(strict_types=1);

/**
 * Tests for the Output helper class
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\Utils;

use PHPUnit\Framework\TestCase;
use Gin0115\WPUnit_Helpers\Output;

class Test_Output extends TestCase {

    /** @testdox It should be possible to capture the output of a function that prints and return this as a string */
    public function test_buffer() {
        $output = Output::buffer( function() {
            Output::println( 'Hello World' );
        } );
        $this->assertSame( 'Hello World' . PHP_EOL, $output );
    }

    /** @testdox It should be possible to print a string to the terminal and have a new line automatically started afterwards */
    public function test_println() {
        $output = Output::buffer( function() {
            Output::println( 'Hello World' );
        } );
        $this->assertSame( 'Hello World' . PHP_EOL, $output );
    }

}