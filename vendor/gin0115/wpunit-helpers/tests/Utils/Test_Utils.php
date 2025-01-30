<?php

declare(strict_types=1);

/**
 * Tests for the Objects helper class
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\Utils;

use Generator;
use PHPUnit\Framework\TestCase;
use Gin0115\WPUnit_Helpers\Utils;

class Test_Utils extends TestCase {

	/** @testdox Can map array with key, value and any passed variables. */
	public function test_map_with_from_array(): void {
		$array   = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3',
		);
		$static1 = 'Arr Static 1';
		$static2 = 'Arr Static 2';

		$result = Utils::iterable_map_with(
			function( $key, $value, $static1, $static2 ): string {
				return \sprintf( '%s -> %s | %s | %s', $key, $value, $static1, $static2 );
			},
			$array,
			$static1,
			$static2
		);

		$this->assertCount( 3, $result );
		$this->assertEquals( "key1 -> value1 | {$static1} | {$static2}", $result[0] );
		$this->assertEquals( "key2 -> value2 | {$static1} | {$static2}", $result[1] );
		$this->assertEquals( "key3 -> value3 | {$static1} | {$static2}", $result[2] );
	}

	/** @testdox Can map iterable/generator with key, value and any passed variables. */
	public function test_map_with_from_generator(): void {

		// Create generator.
		$gernator = function(): Generator {
			yield 'key1' => 'value1';
			yield 'key2' => 'value2';
			yield 'key3' => 'value3';
		};

		$static1 = 'Itr Static 1';
		$static2 = 'Itr Static 2';

		$result = Utils::iterable_map_with(
			function( $key, $value, $static1, $static2 ): string {
				return \sprintf( '%s -> %s | %s | %s', $key, $value, $static1, $static2 );
			},
			$gernator(),
			$static1,
			$static2
		);

		$this->assertCount( 3, $result );
		$this->assertEquals( "key1 -> value1 | {$static1} | {$static2}", $result[0] );
		$this->assertEquals( "key2 -> value2 | {$static1} | {$static2}", $result[1] );
		$this->assertEquals( "key3 -> value3 | {$static1} | {$static2}", $result[2] );
	}

	/** @testdox Can map without any statics, just give key & value in callback. */
	public function test_map_with_no_static_properties() {
		$gernator = function(): Generator {
			yield 'key1' => 'value1';
			yield 'key2' => 'value2';
			yield 'key3' => 'value3';
		};

		$result = Utils::iterable_map_with(
			function( $key, $value ): string {
				return \sprintf( '%s -> %s ', $key, $value );
			},
			$gernator()
		);

		$this->assertCount( 3, $result );
		$this->assertEquals( 'key1 -> value1 ', $result[0] );
		$this->assertEquals( 'key2 -> value2 ', $result[1] );
		$this->assertEquals( 'key3 -> value3 ', $result[2] );
	}

	/** @testdox Can remove a dir and all its children. */
	public function test_can_remove_directories_and_contents() {
		// Create tree to remove.
		$base_tree_path = TEST_FIXTURES_PATH . '/FileSystem/dir_tree';
		\mkdir( $base_tree_path );
		\mkdir( $base_tree_path . '/a/' );
		\touch( $base_tree_path . '/a/file.txt' );
		\mkdir( $base_tree_path . '/a/sub/' );
		\mkdir( $base_tree_path . '/a/sub/file.txt' );
		\mkdir( $base_tree_path . '/b/' );
		\mkdir( $base_tree_path . '/c/' );
		\mkdir( $base_tree_path . '/d/' );

		if ( ! \is_dir( $base_tree_path . '/a/' ) ) {
			throw new \Exception( 'FAILED TO CREATE STUBS FOR ' . __FUNCTION__ );
		}

		Utils::recursive_rmdir( $base_tree_path );
		$this->assertFalse( \is_dir( $base_tree_path . '/a/' ) );
		$this->assertFalse( \is_dir( $base_tree_path . '/a/sub/' ) );
		$this->assertFalse( \file_exists( $base_tree_path . '/a/sub/file.txt' ) );
	}

	/** @testdox Silently fails if directory doesnt exist when attempting to remove.     */
	public function test_silently_fails_to_remove_dir_that_doesnt_exist() {
		Utils::recursive_rmdir( 'SOME/FAKE/PATH' );
		$this->assertTrue( true, 'This looks weird, but just checks no errors throw when removing' );
	}

	/** @testdox Gets list of all top level sub directories and no files. */
	public function test_can_get_all_first_level_sub_directories() {
		$base_path = TEST_FIXTURES_PATH . 'FileSystem/directories/';
		$contents  = Utils::get_subdirectories( $base_path );
		$this->assertContains( $base_path . 'first', $contents );
		$this->assertContains( $base_path . 'second', $contents );
		$this->assertNotContains( $base_path . 'file.txt', $contents );
	}

	/** @testdox Throws no error if an invalid directories is used to get all top level sub directories */
	public function test_throws_no_error_if_invalid_dir_passed_to_get_subdirectories() {
		$contents = Utils::get_subdirectories( '/fake/path' );
		$this->assertEmpty( $contents );
		$this->assertIsArray( $contents );

	}

}
