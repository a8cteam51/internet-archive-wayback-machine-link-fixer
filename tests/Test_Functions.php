<?php

/**
 * Unit test for the assorted functions.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;

/**
 * Test_Functions
 */
class Test_Functions extends \WP_UnitTestCase {

	/**
	 * Normalize URL data provider.
	 *
	 * @return array
	 */
	public static function normalize_url_provider(): array {
		return array(
			'HTTPs simple' => array( 'https://example.com', 'https://example.com' ),
			'HTTP simple' => array( 'http://example.com', 'http://example.com' ),
			'HTTP with trailing slash' => array( 'http://example.com/', 'http://example.com' ),
			'URL with special characters in path' => array( 'http://example.com/wiki/Help:Executive+Bios', 'http://example.com/wiki/Help%3AExecutive%2BBios' ),
			'HTTP URL with basic path' => array( 'http://example.com/path/to/resource', 'http://example.com/path/to/resource' ),
			'HTTP URL with path and trailing slash' => array( 'http://example.com/path/to/resource/', 'http://example.com/path/to/resource' ),
			'HTTP URL with spaces in path' => array( 'http://example.com/path with space/to/resource', 'http://example.com/path%20with%20space/to/resource' ),
			'HTTP URL with query parameters' => array( 'http://example.com/path/to/resource?foo=bar', 'http://example.com/path/to/resource?foo%3Dbar' ),
			'HTTP URL with fragment' => array( 'http://example.com/path/to/resource#section1', 'http://example.com/path/to/resource#section1' ),
			'HTTP URL with port' => array( 'http://example.com:8080/path/to/resource', 'http://example.com:8080/path/to/resource' ),
			'HTTP URL with query containing spaces' => array( 'http://example.com/?query with spaces', 'http://example.com/?query%20with%20spaces' ),
			'HTTPs URL with special characters' => array( 'https://example.com/path/to/special&chars', 'https://example.com/path/to/special%26chars' ),
			'HTTP URL with hashbang fragment' => array( 'http://example.com/#!special_characters', 'http://example.com/#!special_characters' ),
		);
	}

	/**
	 * @testdox It should be possible to normalize a URL.
	 *
	 * @dataProvider normalize_url_provider
	 *
	 * @param string $url      The URL to normalize.
	 * @param string $expected The expected normalized URL.
	 *
	 * @return void
	 */
	public function test_can_normalize_url( string $url, string $expected ): void {
		$this->assertSame( $expected, \wpcomsp_wayback_link_fixer_normalize_url( $url ) );
	}

	/**
	 * Check if mb_strimwidth and  mb_strlen are available.
	 *
	 * @testdox It should be possible to check if mb_strimwidth and mb_strlen are available.
	 */
	public function test_mb_strimwidth_and_mb_strlen_available(): void {
		// Get the current php extensions installed.
		$extensions = get_loaded_extensions();
		// Check if mbstring is available and just show a notice.
		if ( ! in_array( 'mbstring', $extensions, true ) ) {

			echo PHP_EOL . '********************************************' . PHP_EOL;
			echo PHP_EOL . '********************************************' . PHP_EOL;
			echo PHP_EOL . '  The mbstring extension is not available.' . PHP_EOL;
			echo PHP_EOL . '********************************************' . PHP_EOL;
			echo PHP_EOL . '********************************************' . PHP_EOL;
		}

		// Check if mb_strimwidth and mb_strlen are available.
		$this->assertTrue( function_exists( 'mb_strimwidth' ), 'The mb_strimwidth function is not available.' );
		$this->assertTrue( function_exists( 'mb_strlen' ), 'The mb_strlen function is not available.' );
	}

}
