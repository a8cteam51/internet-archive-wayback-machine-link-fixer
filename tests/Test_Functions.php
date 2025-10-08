<?php

/**
 * Unit test for the assorted functions.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;

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
			'HTTPs simple'                          => array( 'https://example.com', 'https://example.com' ),
			'HTTP simple'                           => array( 'http://example.com', 'http://example.com' ),
			'HTTP with trailing slash'              => array( 'http://example.com/', 'http://example.com' ),
			'URL with special characters in path'   => array( 'http://example.com/wiki/Help:Executive+Bios', 'http://example.com%2Fwiki%2FHelp%253AExecutive%252BBios' ),
			'HTTP URL with basic path'              => array( 'http://example.com/path/to/resource', 'http://example.com%2Fpath%2Fto%2Fresource' ),
			'HTTP URL with path and trailing slash' => array( 'http://example.com/path/to/resource/', 'http://example.com%2Fpath%2Fto%2Fresource' ),
			'HTTP URL with spaces in path'          => array( 'http://example.com/path with space/to/resource', 'http://example.com%2Fpath%2520with%2520space%2Fto%2Fresource' ),
			'HTTP URL with query parameters'        => array( 'http://example.com/path/to/resource?foo=bar', 'http://example.com%2Fpath%2Fto%2Fresource?foo%3Dbar' ),
			'HTTP URL with fragment'                => array( 'http://example.com/path/to/resource#section1', 'http://example.com%2Fpath%2Fto%2Fresource#section1' ),
			'HTTP URL with port'                    => array( 'http://example.com:8080/path/to/resource', 'http://example.com:8080%2Fpath%2Fto%2Fresource' ),
			'HTTP URL with query containing spaces' => array( 'http://example.com/?query with spaces', 'http://example.com%2F?query%20with%20spaces' ),
			'HTTPs URL with special characters'     => array( 'https://example.com/path/to/special&chars', 'https://example.com%2Fpath%2Fto%2Fspecial%2526chars' ),
			'HTTP URL with hashbang fragment'       => array( 'http://example.com/#!special_characters', 'http://example.com%2F#!special_characters' ),
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
		$this->assertSame( $expected, \iawmlf_normalize_url( $url ) );
	}

	/**
	 * @testdox The constants should be defined and match the plugin metadata.
	 *
	 * @return void
	 */
	public function test_constants_should_be_defined_and_match_plugin_metadata(): void {
		// Get the plugin metadata.
		$plugin_metadata = get_plugin_data( WP_PLUGIN_DIR . '/internet-archive-wayback-machine-link-fixer/internet-archive-wayback-machine-link-fixer.php' );
		$this->assertSame( IAWMLF_VERSION, $plugin_metadata['Version'], 'Version should match the plugin metadata.' );
		$this->assertSame( IAWMLF_MINIMUM_VERSIONS['wp'], $plugin_metadata['RequiresWP'], 'RequiresWP should match the plugin metadata.' );
		$this->assertSame( IAWMLF_MINIMUM_VERSIONS['php'], $plugin_metadata['RequiresPHP'], 'RequiresPHP should match the plugin metadata.' );
	}
}
