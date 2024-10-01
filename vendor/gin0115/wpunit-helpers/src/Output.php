<?php

/**
 * Collection of helper methods for working with output.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers;

class Output {

	/**
	 * Prints a printable and starts a new line.
	 *
	 * @param mixed $arg
	 * @return void
	 */
	public static function println( $arg ): void {
		print $arg . PHP_EOL; // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Basic buffer for catching output of a callable.
	 *
	 * @param callable $action
	 * @return string
	 */
	public static function buffer( callable $action ): string {
		$output = '';
		ob_start();
		$action();
		$output = ob_get_contents();
		ob_end_clean();
		return $output ?: '';
	}
}
