<?php

/**
 * Test Settings
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Settings;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Abstract_Migration;

class Test_Settings extends \WP_UnitTestCase {

	/**
	 * Clear all the options before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( Settings::ALLOWED_POST_TYPES );
		delete_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY );
		delete_option( Settings::MIGRATIONS_KEY );
		delete_option( Settings::LINK_EXCLUSIONS );
		delete_option( Settings::SCAN_EXISTING_POSTS );
		delete_option( Settings::FIXER_OPTION );
		delete_option( Settings::CAST_ARCHIVED_TO_HTTPS );
		delete_option( Settings::LINK_ICON );

		update_option( Settings::PROCESS_LINKS, true );
	}

	/**
	 * @testdox It should be possible to get the link table name, prefixed with the WordPress table prefix.
	 *
	 * @return void
	 */
	public function test_can_get_link_table_name(): void {
		$table_name = Settings::get_link_table_name();
		$prefix     = $GLOBALS['wpdb']->prefix;

		$this->assertStringStartsWith( $prefix, $table_name );
		$this->assertStringEndsWith( Settings::LINK_TABLE, $table_name );
	}

	/**
	 * @testdox It should be possible to get the allowed post types.
	 *
	 * @return void
	 */
	public function test_can_get_allowed_post_types(): void {
		// Set the allowed post types.
		$allowed_post_types = array( 'post', 'page', 'other' );
		update_option( Settings::ALLOWED_POST_TYPES, $allowed_post_types );

		$this->assertEquals( $allowed_post_types, Settings::get_allowed_post_types() );
	}

	/**
	 * @testdox It should be possible to set if the tables should be dropped or not.
	 *
	 * @return void
	 */
	public function test_can_set_drop_tables_on_uninstall(): void {
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, false );
		$this->assertFalse( Settings::drop_tables_on_uninstall() );
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, true );
		$this->assertTrue( Settings::drop_tables_on_uninstall() );
	}

	/**
	 * @testdox It should be possible to set and get the migrations.
	 *
	 * @return void
	 */
	public function test_can_set_and_get_migrations(): void {
		$migration_1 = $this->createMock( Abstract_Migration::class );
		$migration_2 = $this->createMock( Abstract_Migration::class );
		$migrations  = array( get_class( $migration_1 ), get_class( $migration_2 ) );
		Settings::update_migrations( $migrations );
		$this->assertEquals( $migrations, Settings::migrations() );
	}

	/**
	 * @testdox It should be possible to get the link checker timeout in seconds and control this via a filter.
	 *
	 * @return void
	 */
	public function test_can_get_link_checker_timeout(): void {
		add_filter(
			'iawmlf_link_checker_timeout',
			function () {
				return 20;
			}
		);

		$timeout = Settings::get_link_checker_timeout();
		$this->assertEquals( 20, $timeout );

		// Clean up.
		remove_all_filters( 'iawmlf_link_checker_timeout' );
	}

	/**
	 * @testdox It should be possible to get all excluded links from the database.
	 *
	 * @return void
	 */
	public function test_can_get_excluded_links(): void {
		$excluded_links = array(
			'https://example.com/1',
			'https://example.com/2',
			'https://example.com/3',
		);

		// Set the excluded links.
		update_option( Settings::LINK_EXCLUSIONS, $excluded_links );

		$this->assertEquals( $excluded_links, Settings::get_link_exclusions() );
	}

	/**
	 * @testdox It should be possible to add links to the exclusion list via a filter, to ensure some can not be removed.
	 *
	 * @return void
	 */
	public function test_can_add_links_to_exclusion_list_via_filter(): void {
		add_filter(
			'iawmlf_link_exclusions',
			function ( array $links ) {
				$links[] = 'https://example.com/4';
				return $links;
			}
		);

		$this->assertEquals( array( 'https://example.com/4' ), Settings::get_link_exclusions() );

		// Clean up.
		remove_all_filters( 'iawmlf_link_exclusions' );
	}

	/**
	 * @testdox It should be possible to change how many posts are processed per batch via a filter
	 *
	 * @return void
	 */
	public function test_can_change_posts_per_batch_via_filter(): void {

		// By default, the posts per batch is 10.
		$this->assertEquals( 10, Settings::get_posts_per_batch() );

		add_filter(
			'iawmlf_posts_per_batch',
			function () {
				return 5;
			}
		);

		$this->assertEquals( 5, Settings::get_posts_per_batch() );

		// Clean up.
		remove_all_filters( 'iawmlf_posts_per_batch' );
	}

	/**
	 * @testdox It should be possible to change the duration between link checks via a filter.
	 *
	 * @return void
	 */
	public function test_can_change_link_check_duration_via_filter(): void {
		// By default, the link check duration is 3 day.
		$this->assertEquals( 3, Settings::get_link_check_duration() );

		add_filter(
			'iawmlf_link_check_duration_in_days',
			function () {
				return 2;
			}
		);

		$this->assertEquals( 2, Settings::get_link_check_duration() );

		// Clean up.
		remove_all_filters( 'iawmlf_link_check_duration_in_days' );
	}

	/**
	 * @testdox It should be possible to change the http status codes which are treated as OK via a filter.
	 *
	 * @return void
	 */
	public function test_can_change_valid_http_status_codes_via_filter(): void {
		// By default, the valid http status codes are 200, 206 and 429.
		$this->assertEquals( array( 200, 206, 429 ), Settings::get_valid_http_status_codes() );

		add_filter(
			'iawmlf_valid_http_status_codes',
			function () {
				return array( 200, 206, 301 );
			}
		);

		$this->assertEquals( array( 200, 206, 301 ), Settings::get_valid_http_status_codes() );

		// Clean up.
		remove_all_filters( 'iawmlf_valid_http_status_codes' );
	}

	/**
	 * @testdox It should be possible to define if existing posts should be scanned in the settings.
	 *
	 * @return void
	 */
	public function test_can_define_if_existing_posts_should_be_scanned(): void {
		// By default, existing posts should not be scanned.
		$this->assertFalse( Settings::should_scan_existing_posts() );

		\update_option( Settings::SCAN_EXISTING_POSTS, true );

		$this->assertTrue( Settings::should_scan_existing_posts() );
	}

	/**
	 * @testdox It should be possible to force the default value for getting if existing posts should be scanned.
	 *
	 * @return void
	 */
	public function test_can_force_default_value_for_should_scan_existing_posts(): void {
		// Remove the option to ensure we are using the default.
		\delete_option( Settings::SCAN_EXISTING_POSTS );

		// When we force the default value to false, it should return false.
		$this->assertFalse( Settings::should_scan_existing_posts( false ) );

		// When we force the default value to true, it should return true.
		$this->assertTrue( Settings::should_scan_existing_posts( true ) );
	}

	/**
	 * @testdox It should be possible to change what happens when a broken link is encountered and it should replace by default.
	 *
	 * @return void
	 */
	public function test_can_change_fixer_option(): void {
		// By default, the fixer option is replace.
		$this->assertEquals( Settings::FIXER_OPTION_REPLACE_LINK, Settings::get_fixer_option() );

		\update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_DO_NOTHING );

		$this->assertEquals( Settings::FIXER_OPTION_DO_NOTHING, Settings::get_fixer_option() );
	}

	/**
	 * @testdox If the option to process links is not enable, allowing scanning existing posts, should return false.
	 *
	 * @return void
	 */
	public function test_should_scan_existing_posts_should_return_false_if_option_is_not_enabled(): void {
		update_option( Settings::PROCESS_LINKS, false );
		$this->assertFalse( Settings::should_scan_existing_posts() );

		// Set the scan existing posts to true.
		update_option( Settings::SCAN_EXISTING_POSTS, true );
		$this->assertFalse( Settings::should_scan_existing_posts() );

		// Set the process links to true.
		update_option( Settings::PROCESS_LINKS, true );
		$this->assertTrue( Settings::should_scan_existing_posts() );

		// Set the scan existing posts to false.
		update_option( Settings::SCAN_EXISTING_POSTS, false );
	}

	/**
	 * @testdox It should be possible to get the cast to HTTPS option.
	 *
	 * @since 1.3.5
	 *
	 * @return void
	 */
	public function test_can_get_cast_to_https_option_with_fallbacks(): void {
		// By default, the cast to HTTPS option should be false.
		$this->assertFalse( Settings::should_cast_archived_to_https() );

		// When set.
		update_option( Settings::CAST_ARCHIVED_TO_HTTPS, true );
		$this->assertTrue( Settings::should_cast_archived_to_https() );
	}

	/**
	 * @testdox It should be possible to clear all the settings, this can then be used to clear on uninstall.
	 *
	 * @return void
	 */
	public function test_can_clear_all_settings(): void {
		// Set all the options with mock data.
		update_option( Settings::PROCESS_LINKS, true );
		update_option( Settings::ALLOWED_POST_TYPES, array( 'post', 'page', 'custom' ) );
		update_option( Settings::MIGRATIONS_KEY, array( 'Migration_1', 'Migration_2' ) );
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, true );
		update_option( Settings::LINK_EXCLUSIONS, array( 'https://example.com/1', 'https://example.com/2' ) );
		update_option( Settings::SCAN_EXISTING_POSTS, true );
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'secret_key' );
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, 'access_key' );
		update_option( Settings::ARCHIVE_ORG_STATUS_KEY, 'status_key' );
		update_option( Settings::ARCHIVE_ORG_CREDS_VALID_KEY, true );
		update_option( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS, true );
		update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post', 'page', 'custom' ) );
		update_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE, true );
		update_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL, 10 );
		update_option( Settings::POST_ACTIVATION_ONBOARDING_KEY, 'pending' );
		update_option( Settings::MINIMUM_CHECKS_BEFORE_BROKEN, 3 );
		update_option( Settings::LINK_CHECK_DURATION_IN_DAYS, 14 );
		update_option( Settings::SETUP_WIZARD_STEP_KEY, 'step-2' );
		update_option( Settings::SETUP_WIZARD_COMPLETED_KEY, true );
		update_option( Settings::CAST_ARCHIVED_TO_HTTPS, true );
		update_option( Settings::LINK_ICON, 'ia_logo_after' );

		// Clear all the settings.
		Settings::clear_all_options();

		// Check that all the settings are cleared.
		$this->assertEmpty( get_option( Settings::PROCESS_LINKS ) );
		$this->assertEmpty( get_option( Settings::ALLOWED_POST_TYPES ) );
		$this->assertEmpty( get_option( Settings::MIGRATIONS_KEY ) );
		$this->assertEmpty( get_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY ) );
		$this->assertEmpty( get_option( Settings::LINK_EXCLUSIONS ) );
		$this->assertEmpty( get_option( Settings::SCAN_EXISTING_POSTS ) );
		$this->assertEmpty( get_option( Settings::FIXER_OPTION ) );
		$this->assertEmpty( get_option( Settings::ARCHIVE_ORG_SECRET_KEY ) );
		$this->assertEmpty( get_option( Settings::ARCHIVE_ORG_ACCESS_KEY ) );
		$this->assertEmpty( get_option( Settings::ARCHIVE_ORG_STATUS_KEY ) );
		$this->assertEmpty( get_option( Settings::ARCHIVE_ORG_CREDS_VALID_KEY ) );
		$this->assertEmpty( get_option( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS ) );
		$this->assertEmpty( get_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES ) );
		$this->assertEmpty( get_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE ) );
		$this->assertEmpty( get_option( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL ) );
		$this->assertEmpty( get_option( Settings::POST_ACTIVATION_ONBOARDING_KEY ) );
		$this->assertEmpty( get_option( Settings::MINIMUM_CHECKS_BEFORE_BROKEN ) );
		$this->assertEmpty( get_option( Settings::LINK_CHECK_DURATION_IN_DAYS ) );
		$this->assertEmpty( get_option( Settings::SETUP_WIZARD_STEP_KEY ) );
		$this->assertEmpty( get_option( Settings::SETUP_WIZARD_COMPLETED_KEY ) );
		$this->assertEmpty( get_option( Settings::CAST_ARCHIVED_TO_HTTPS ) );
		$this->assertEmpty( get_option( Settings::LINK_ICON ) );
	}

	/**
	 * @testdox It should be possible to check if the HTML link output should be rendered in the frontend.
	 *
	 * @since 1.3.1
	 *
	 * @return void
	 */
	public function test_should_render_html_link_output(): void {
		// Set the option to replace link.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );
		$this->assertTrue( Settings::should_render_html_link_output() );

		// Set the option to do nothing.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_DO_NOTHING );
		$this->assertFalse( Settings::should_render_html_link_output() );

		// Set the option to replace link via a filter.
		add_filter(
			'iawmlf_should_render_html_link_output',
			function () {
				return true;
			}
		);
		$this->assertTrue( Settings::should_render_html_link_output() );

		// Clean up.
		remove_all_filters( 'iawmlf_should_render_html_link_output' );
	}

	/**
	 * @testdox It should return no icon when the link icon option is not set.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_link_icon_defaults_to_none_when_not_set(): void {
		$this->assertEquals( Settings::LINK_ICON_NONE, Settings::get_link_icon() );
	}

	/**
	 * @testdox It should return no icon when the link icon option is set to an invalid value.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_link_icon_defaults_to_none_when_invalid(): void {
		update_option( Settings::LINK_ICON, 'does_not_exist' );
		$this->assertEquals( Settings::LINK_ICON_NONE, Settings::get_link_icon() );
	}

	/**
	 * @testdox It should return the selected link icon when set to a valid value.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_link_icon_returns_selected_value(): void {
		update_option( Settings::LINK_ICON, 'ia_logo_after' );
		$this->assertEquals( 'ia_logo_after', Settings::get_link_icon() );
	}

	/**
	 * @testdox It should return an empty CSS string when the link icon is set to none.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_link_icon_css_returns_empty_when_none(): void {
		$this->assertEmpty( Settings::get_link_icon_css() );
	}

	/**
	 * @testdox It should return a CSS rule when the link icon is set to a valid icon.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_link_icon_css_returns_css_when_set(): void {
		update_option( Settings::LINK_ICON, 'ia_logo_after' );
		$css = Settings::get_link_icon_css();
		$this->assertNotEmpty( $css );
		$this->assertStringContainsString( ':after', $css );
	}

	/**
	 * @testdox It should be possible to add custom icons via the iawmlf_link_icons filter.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_can_add_custom_icons_via_filter(): void {
		add_filter(
			'iawmlf_link_icons',
			function ( array $icons ) {
				$icons[] = array(
					'id'       => 'custom',
					'name'     => 'Custom Icon',
					'icon_css' => 'content: "\2713";',
				);
				return $icons;
			}
		);

		// Should have before and after variants.
		update_option( Settings::LINK_ICON, 'custom_before' );
		$this->assertEquals( 'custom_before', Settings::get_link_icon() );
		$this->assertStringContainsString( ':before', Settings::get_link_icon_css() );

		update_option( Settings::LINK_ICON, 'custom_after' );
		$this->assertEquals( 'custom_after', Settings::get_link_icon() );
		$this->assertStringContainsString( ':after', Settings::get_link_icon_css() );

		// Clean up.
		remove_all_filters( 'iawmlf_link_icons' );
	}

	/**
	 * @testdox The available link icons should always include the none option and the IA logo before and after variants.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function test_get_available_link_icons_returns_default_options(): void {
		$icons = Settings::get_available_link_icons();
		$ids   = array_column( $icons, 'id' );

		$this->assertContains( Settings::LINK_ICON_NONE, $ids );
		$this->assertContains( 'ia_logo_before', $ids );
		$this->assertContains( 'ia_logo_after', $ids );
	}
}
