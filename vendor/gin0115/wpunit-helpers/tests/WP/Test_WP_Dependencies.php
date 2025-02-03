<?php

declare(strict_types=1);

/**
 * Tests for WP_Dependencies helper
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP;

use Exception;
use Gin0115\WPUnit_Helpers\Utils;

class Test_WP_Dependencies extends \WP_UnitTestCase {

	protected const TEST_PLUGIN_PATH = TEST_FIXTURES_PATH . '/FileSystem/wordpress/wp-content/plugins';

	public function setUp(): void {
		parent::setUp();

		// Clear up all installed plugins.
		foreach ( Utils::get_subdirectories( self::TEST_PLUGIN_PATH ) as $path ) {
			Utils::recursive_rmdir( $path );
		}
	}

	/** @testdox Can install a plugin from its URL */
	public function test_can_install_plugin_from_remote_zip(): void {
		$plugin_url = 'https://downloads.wordpress.org/plugin/woocommerce.4.9.2.zip';
		$wp_path    = TEST_FIXTURES_PATH . '/FileSystem/wordpress';

		// Install WooComerce.
		WP_Dependencies::install_remote_plugin_from_zip( $plugin_url, $wp_path );

		$this->assertDirectoryExists( self::TEST_PLUGIN_PATH . '/woocommerce' );
	}

	/** @testdox Aborts if attempting to download a none existant file */
	public function test_is_error_with_invlaid_url(): void {
		$plugin_url = 'https://downloads.wordpress.org/plugin/woocommerce.4.9.2.zip';
		$wp_path    = TEST_FIXTURES_PATH . '/FileSystem/wordpress';

		$this->expectException( Exception::class );
		WP_Dependencies::install_remote_plugin_from_zip( 'fakeURL', $wp_path );
	}

	/** @testdox Aborts if attempting to download a none existant file */
	public function test_is_error_with_none_zip(): void {
		$plugin_url = 'https://github.com/gin0115/pinkcrab_function_constructors/blob/develop/README.md';
		$wp_path    = TEST_FIXTURES_PATH . '/FileSystem/wordpress';

		$this->expectException( Exception::class );
		WP_Dependencies::install_remote_plugin_from_zip( $plugin_url, $wp_path );
	}

	/** @testdox Activates a plugin based on its name */
	public function test_activate_plugin(): void {
		\copy(
			TEST_FIXTURES_PATH . '/WP_Dependencies/Stub_Plugin.php',
			TEST_WP_INSTANCE_PATH . '/wp-content/plugins/Stub_Plugin.php'
		);

		WP_Dependencies::activate_plugin( 'Stub_Plugin.php' );
		$this->assertTrue( \is_plugin_active( 'Stub_Plugin.php' ) );
	}

	/** @testdox It should be possible to check if a plugin is installed. */
	public function test_plugin_installed(): void {
		$plugin_url = 'https://downloads.wordpress.org/plugin/sensei-lms.4.0.0.zip';
		$wp_path    = TEST_FIXTURES_PATH . '/FileSystem/wordpress';

		$this->assertFalse(
			WP_Dependencies::plugin_installed( $plugin_url, $wp_path )
		);

		WP_Dependencies::install_remote_plugin_from_zip( $plugin_url, $wp_path );

		$this->assertFalse(
			WP_Dependencies::plugin_installed( $plugin_url, $wp_path )
		);
	}

	/** @testdox It should be possible to check if a plugin is active. */
	public function test_is_plugin_active(): void
	{
		$plugin_url = 'https://downloads.wordpress.org/plugin/sensei-lms.4.0.0.zip';
		$plugin = 'sensei-lms/sensei-lms.php';
		$wp_path    = TEST_FIXTURES_PATH . '/FileSystem/wordpress';

		$this->assertFalse(
			WP_Dependencies::plugin_active( $plugin )
		);

		// Install and activate.
		WP_Dependencies::install_remote_plugin_from_zip( $plugin_url, $wp_path );
		WP_Dependencies::activate_plugin( $plugin );
		
		$this->assertTrue(
			WP_Dependencies::plugin_active( $plugin )
		);
	}

}
