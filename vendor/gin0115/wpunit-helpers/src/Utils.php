<?php

/**
 * Functions which have no real place to live.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers;

use PinkCrab\FunctionConstructors\Arrays as Arr;

use function PinkCrab\FunctionConstructors\Arrays\filterMap;

class Utils {

	/**
	 * Array map, which gives access to array key and a selection of static
	 * values.
	 *
	 * @param callable $function
	 * @param iterable<mixed> $data
	 * @param mixed ...$with Values to pass to the callback function. function($key, $value, ...$with)
	 * @return array<int, mixed>
	 */
	public static function iterable_map_with( callable $function, iterable $data, ...$with ): array {
		$return = array();
		foreach ( $data as $key => $value ) {
			$return[] = $function( $key, $value, ...$with );
		}
		return $return;
	}

	/**
	 * Recursively remove a directory and all its contents.
	 *
	 * @param string $dir
	 * @return void
	 */
	public static function recursive_rmdir( string $dir ): void {
		if ( is_dir( $dir ) ) {
			$contents = scandir( $dir );

			// Loop through contents.
			foreach ( $contents ?: array() as $object ) {
				if ( $object !== '.' && $object !== '..' ) {
					if ( filetype( $dir . '/' . $object ) === 'dir' ) {
						self::recursive_rmdir( $dir . '/' . $object );
					} else {
						unlink( $dir . '/' . $object );
					}
				}
			}
			if ( is_array( $contents ) ) {
				reset( $contents );
			}
			rmdir( $dir );
		}
	}

	/**
	 * Gets an array of all top level sub directories from a parent directory.
	 *
	 * @param string $dir
	 * @return array<string>
	 */
	public static function get_subdirectories( string $dir ): array {
		// Bail if invalid directory.
		if ( ! \file_exists( $dir ) ) {
			return array();
		}

		// Get the first level files/dirs
		$contents = \scandir( $dir );

		return Arr\filterMap(
			// Ensure only directorys that exist are returned.
			function( string $element ) use ( $dir ): bool {
				return \file_exists( $dir . \DIRECTORY_SEPARATOR . $element )
					&& \is_dir( $dir . \DIRECTORY_SEPARATOR . $element );
			},
			// Map with full url.
			function( string $dir_name ) use ( $dir ): string {
				return realpath( $dir . \DIRECTORY_SEPARATOR . $dir_name ) ?: '';
			}
		)( array_diff( $contents ?: array(), array( '.', '..' ) ) );
	}
}
