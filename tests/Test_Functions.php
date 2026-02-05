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
	 * Status code human readable data provider.
	 *
	 * @return array
	 */ public static function statusCodeHumanReadable(): array {
		return array(
			'error:no-access'                   => array( 'error:no-access', 'Target URL could not be accessed (status=403).' ),
			'error:blocked'                     => array( 'error:blocked', 'The target site is blocking us (HTTP status=999).' ),
			'error:not-found'                   => array( 'error:not-found', 'Target URL not found (status=404).' ),
			'error:browsing-timeout'            => array( 'error:browsing-timeout', 'SPN2 back-end headless browser timeout.' ),
			'error:capture-location-error'      => array( 'error:capture-location-error', 'SPN2 back-end cannot find the created capture location. (system error).' ),
			'error:internal-server-error'       => array( 'error:internal-server-error', 'SPN internal server error.' ),
			'error:job-failed'                  => array( 'error:job-failed', 'Capture failed due to system error.' ),
			'error:proxy-error'                 => array( 'error:proxy-error', 'SPN2 back-end proxy error.' ),
			'error:too-many-daily-captures'     => array( 'error:too-many-daily-captures', 'This URL has been captured 10 times today. We cannot make any more captures.' ),
			'error:too-many-requests'           => array( 'error:too-many-requests', 'The target host has received too many requests from SPN and it is blocking it. (HTTP status=429). Note that captures to the same host will be delayed for 10-20s after receiving this response to remedy the situation.' ),
			'error:max-daily-bandwidth'         => array( 'error:max-daily-bandwidth', 'An authenticated user can archive up to 5GB per day.' ),
			'error:max-daily-bandwidth-from-ip' => array( 'error:max-daily-bandwidth-from-ip', 'An anonymous user can archive up to 2GB per day.' ),
			'error:max-daily-bandwidth-host'    => array( 'error:max-daily-bandwidth-host', 'SPN2 can archive up to 100GB per day from a host.' ),
			'error:browsing-timeout'            => array( 'error:browsing-timeout', 'SPN2 back-end headless browser timeout.' ),
			'error:capture-location-error'      => array( 'error:capture-location-error', 'SPN2 back-end cannot find the created capture location. (system error).' ),
			'error:internal-server-error'       => array( 'error:internal-server-error', 'SPN internal server error.' ),
			'error:job-failed'                  => array( 'error:job-failed', 'Capture failed due to system error.' ),
			'error:proxy-error'                 => array( 'error:proxy-error', 'SPN2 back-end proxy error.' ),
			'error:too-many-daily-captures'     => array( 'error:too-many-daily-captures', 'This URL has been captured 10 times today. We cannot make any more captures.' ),
			'error:too-many-requests'           => array( 'error:too-many-requests', 'The target host has received too many requests from SPN and it is blocking it. (HTTP status=429). Note that captures to the same host will be delayed for 10-20s after receiving this response to remedy the situation.' ),
			'error:max-daily-bandwidth'         => array( 'error:max-daily-bandwidth', 'An authenticated user can archive up to 5GB per day.' ),
			'error:max-daily-bandwidth-from-ip' => array( 'error:max-daily-bandwidth-from-ip', 'An anonymous user can archive up to 2GB per day.' ),
			'error:max-daily-bandwidth-host'    => array( 'error:max-daily-bandwidth-host', 'SPN2 can archive up to 100GB per day from a host.' ),
			'error:other'                       => array( 'error:other', 'Uknown: error:other' ),
			'info:not-valid'                    => array( 'info:not-valid', 'info:not-valid' ),
		);
}

	/**
	 * Status codes that mark link as excluded data provider.
	 *
	 * @return array
	 */ public static function statusMarksLinkExcluded(): array {
		return array(
			'error:browsing-timeout'            => array( 'error:browsing-timeout', false ),
			'error:capture-location-error'      => array( 'error:capture-location-error', false ),
			'error:internal-server-error'       => array( 'error:internal-server-error', false ),
			'error:job-failed'                  => array( 'error:job-failed', false ),
			'error:proxy-error'                 => array( 'error:proxy-error', false ),
			'error:too-many-daily-captures'     => array( 'error:too-many-daily-captures', false ),
			'error:too-many-requests'           => array( 'error:too-many-requests', false ),
			'error:max-daily-bandwidth'         => array( 'error:max-daily-bandwidth', false ),
			'error:max-daily-bandwidth-from-ip' => array( 'error:max-daily-bandwidth-from-ip', false ),
			'error:max-daily-bandwidth-host'    => array( 'error:max-daily-bandwidth-host', false ),
			'error:no-access'                   => array( 'error:no-access', true ),
			'error:blocked'                     => array( 'error:blocked', true ),
			'error:not-found'                   => array( 'error:not-found', true ),
			'error:other'                       => array( 'error:other', true ),
			'info:not-valid'                    => array( 'info:not-valid', false ),
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
	 * @param string  $url      The URL to check.
	 * @param boolean $expected The expected result.
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

	/**
	 * @testdox The function should correctly identify excluded status codes and return back a human readable message for the status code. If invalid status code is passed it should return it status code.
	 *
	 * @dataProvider statusCodeHumanReadable
	 *
	 * @param string $status_code      The status code to check.
	 * @param string $expected_message The expected human readable message.
	 *
	 * @return void
	 */
public function test_excluded_status_codes( string $status_code, string $expected_message ): void {
	$this->assertEquals( $expected_message, iawmlf_get_human_readable_status_message( $status_code ) );
}

	/**
	 * @testdox Some status imply we can never get the snapshot, but others dont. Based on the error we should mark or not mark the link as excluded.
	 *
	 * @dataProvider statusMarksLinkExcluded
	 *
	 * @param string  $status_code The status code.
	 * @param boolean $expected    The expected outcome.
	 *
	 * @return void
	 */
public function test_status_codes_that_mark_link_as_excluded( string $status_code, bool $expected ): void {
	$this->assertEquals( $expected, iawmlf_is_excluded_status_code( $status_code ) );
}
}
