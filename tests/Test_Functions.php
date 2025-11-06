<?php

/**
 * Unit test for the assorted functions.
 *
 * @since 1.3.0
 *
 * @coversDefaultClass ::iawmlf_normalize_url
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
			'URL with special characters in path'   => array( 'http://example.com/wiki/Help:Executive+Bios', 'http://example.com/wiki/Help%3AExecutive%20Bios' ),
			'HTTP URL with basic path'              => array( 'http://example.com/path/to/resource', 'http://example.com/path/to/resource' ),
			'HTTP URL with path and trailing slash' => array( 'http://example.com/path/to/resource/', 'http://example.com/path/to/resource' ),
			'HTTP URL with spaces in path'          => array( 'http://example.com/path with space/to/resource', 'http://example.com/path%20with%20space/to/resource' ),
			'HTTP URL with query parameters'        => array( 'http://example.com/path/to/resource?foo=bar', 'http://example.com/path/to/resource?foo=bar' ),
			'HTTP URL with fragment'                => array( 'http://example.com/path/to/resource#section1', 'http://example.com/path/to/resource#section1' ),
			'HTTP URL with port'                    => array( 'http://example.com:8080/path/to/resource', 'http://example.com:8080/path/to/resource' ),
			'HTTP URL with query containing spaces' => array( 'http://example.com/?query with spaces', 'http://example.com/?query%20with%20spaces' ),
			'HTTPs URL with special characters'     => array( 'https://example.com/path/to/special&chars', 'https://example.com/path/to/special%26chars' ),
			'HTTP URL with hashbang fragment'       => array( 'http://example.com/#!special_characters', 'http://example.com/#!special_characters' ),
			'URL with already encoded characters'   => array( 'http://example.com/path%20with%20spaces', 'http://example.com/path%20with%20spaces' ),
			'URL with mixed encoding'               => array( 'http://example.com/path%20with/other spaces', 'http://example.com/path%20with/other%20spaces' ),
			'URL with special chars in query'       => array( 'http://example.com/?param=value&other=test', 'http://example.com/?param=value&other=test' ),
			'URL with unicode characters'           => array( 'http://example.com/path/with/unicode/测试', 'http://example.com/path/with/unicode/%E6%B5%8B%E8%AF%95' ),
			'Real-world CanSpace URL with %20'      => array( 'https://www.canspace.ca/blog/hosting-servers/what-does-%20-mean-in-a-web-address/', 'https://www.canspace.ca/blog/hosting-servers/what-does-%20-mean-in-a-web-address' ),
			'Real-world Stack Overflow search'      => array( 'https://stackoverflow.com/search?q=php%20url%20encoding&s=b474a178-b42b-4310-ab1c-267331ff5fc3', 'https://stackoverflow.com/search?q=php%20url%20encoding&s=b474a178-b42b-4310-ab1c-267331ff5fc3' ),
		);
	}

	/**
	 * Archive link check data provider.
	 *
	 * @return array
	 */
	public static function is_archive_link_provider(): array {
		return array(
			'HTTPS web.archive.org'         => array( 'https://web.archive.org/web/20230101000000/https://example.com', true ),
			'HTTP web.archive.org'          => array( 'http://web.archive.org/web/20230101000000/https://example.com', true ),
			'HTTPS web-wp.archive.org'      => array( 'https://web-wp.archive.org/web/20230101000000/https://example.com', true ),
			'HTTP web-wp.archive.org'       => array( 'http://web-wp.archive.org/web/20230101000000/https://example.com', true ),
			'HTTPS web.archive.org no path' => array( 'https://web.archive.org/web/', true ),
			'HTTP web.archive.org no path'  => array( 'http://web.archive.org/web/', true ),
			'Regular HTTPS URL'             => array( 'https://example.com', false ),
			'Regular HTTP URL'              => array( 'http://example.com', false ),
			'Archive.org but not web'       => array( 'https://archive.org/details/something', false ),
			'Contains but not starts with'  => array( 'https://example.com/web.archive.org/web/', false ),
			'Almost matching URL'           => array( 'https://web.archive.org/details/', false ),
			'Empty string'                  => array( '', false ),
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
	 * @testdox It should correctly identify Internet Archive links.
	 *
	 * @dataProvider is_archive_link_provider
	 *
	 * @param string $url      The URL to check.
	 * @param bool   $expected The expected result.
	 *
	 * @return void
	 */
	public function test_can_identify_archive_links( string $url, bool $expected ): void {
		$this->assertSame( $expected, \iawmlf_is_archive_link( $url ) );
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
