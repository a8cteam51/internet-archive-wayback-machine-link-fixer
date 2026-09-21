<?php

/**
 * Structural tests for the Settings Page.
 *
 * These assert invariants over whatever the page registers, never a fixed list of
 * fields, so adding or renaming a setting does not mean editing a test.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\System_Client;

/**
 * Test_Settings_Page
 *
 * @group Dashboard
 * @group Settings_Page
 */
class Test_Settings_Page extends \WP_UnitTestCase {

	/**
	 * The globals WordPress writes settings registrations into.
	 *
	 * @var array<string, mixed>
	 */
	private $globals = array();

	public function set_up(): void {
		parent::set_up();

		foreach ( array( 'wp_registered_settings', 'wp_settings_sections', 'wp_settings_fields', 'new_allowed_options', 'submenu' ) as $key ) {
			$this->globals[ $key ] = $GLOBALS[ $key ] ?? null;
		}

		if ( ! function_exists( 'add_submenu_page' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	public function tear_down(): void {
		foreach ( $this->globals as $key => $value ) {
			$GLOBALS[ $key ] = $value;
		}

		unset( $_GET['page'], $_GET['settings-updated'] );

		remove_all_filters( 'iawmlf_system_client' );

		parent::tear_down();
	}

	/**
	 * Register the fields as though the settings screen were being viewed.
	 *
	 * @return void
	 */
	private function register_on_settings_screen(): void {
		$_GET['page'] = Settings_Page::PAGE_SLUG;

		( new Settings_Page() )->register_fields();
	}

	/**
	 * Every setting registered against this page.
	 *
	 * @return array<string, array>
	 */
	private function plugin_settings(): array {
		return array_filter(
			get_registered_settings(),
			function ( array $setting ): bool {
				return Settings_Page::PAGE_SLUG === ( $setting['group'] ?? '' );
			}
		);
	}

	/**
	 * @testdox Registration is skipped away from the settings screen, so no other admin page pays for it.
	 *
	 * @return void
	 */
	public function test_fields_are_not_registered_away_from_the_settings_screen(): void {
		$GLOBALS['pagenow'] = 'index.php';

		( new Settings_Page() )->register_fields();

		$this->assertEmpty( $this->plugin_settings() );
	}

	/**
	 * @testdox Every setting is registered against the page slug and carries the plugin prefix.
	 *
	 * @return void
	 */
	public function test_settings_are_registered_against_the_page_slug(): void {
		$this->register_on_settings_screen();

		$settings = $this->plugin_settings();

		$this->assertNotEmpty( $settings );

		foreach ( array_keys( $settings ) as $key ) {
			$this->assertStringStartsWith( Settings::SETTINGS_PREFIX, $key, "{$key} is not prefixed." );
		}
	}

	/**
	 * @testdox Every setting has a sanitize callback that can actually be called - an unsaved value would otherwise reach the option table raw.
	 *
	 * @return void
	 */
	public function test_every_setting_has_a_callable_sanitize_callback(): void {
		$this->register_on_settings_screen();

		foreach ( $this->plugin_settings() as $key => $setting ) {
			$this->assertTrue(
				is_callable( $setting['sanitize_callback'] ?? null ),
				"{$key} has no usable sanitize callback."
			);
		}
	}

	/**
	 * @testdox Every declared default matches the type it is declared as, so an absent option reads back as the right shape.
	 *
	 * @return void
	 */
	public function test_every_default_matches_its_declared_type(): void {
		$this->register_on_settings_screen();

		// WordPress setting types mapped to what gettype() reports.
		$types = array(
			'boolean' => 'boolean',
			'integer' => 'integer',
			'number'  => 'double',
			'string'  => 'string',
			'array'   => 'array',
			'object'  => 'array',
		);

		foreach ( $this->plugin_settings() as $key => $setting ) {
			if ( ! array_key_exists( 'default', $setting ) || ! isset( $types[ $setting['type'] ?? '' ] ) ) {
				continue;
			}

			$this->assertSame(
				$types[ $setting['type'] ],
				gettype( $setting['default'] ),
				"{$key} is declared as {$setting['type']} but its default is not."
			);
		}
	}

	/**
	 * @testdox Every settings field has a render callback that exists - a renamed render method would otherwise fatal on the settings screen.
	 *
	 * @return void
	 */
	public function test_every_field_has_a_callable_render_callback(): void {
		$this->register_on_settings_screen();

		$fields = $GLOBALS['wp_settings_fields'][ Settings_Page::PAGE_SLUG ] ?? array();

		$this->assertNotEmpty( $fields );

		foreach ( $fields as $section => $section_fields ) {
			foreach ( $section_fields as $id => $field ) {
				$this->assertTrue(
					is_callable( $field['callback'] ?? null ),
					"The render callback for {$id} in {$section} cannot be called."
				);
			}
		}
	}

	/**
	 * @testdox Every field belongs to a section that was added - a field in an unknown section is silently never rendered.
	 *
	 * @return void
	 */
	public function test_every_field_belongs_to_a_registered_section(): void {
		$this->register_on_settings_screen();

		$sections = array_keys( $GLOBALS['wp_settings_sections'][ Settings_Page::PAGE_SLUG ] ?? array() );
		$fields   = $GLOBALS['wp_settings_fields'][ Settings_Page::PAGE_SLUG ] ?? array();

		$this->assertNotEmpty( $sections );

		foreach ( array_keys( $fields ) as $section ) {
			$this->assertContains( $section, $sections, "Fields are attached to {$section}, which was never added." );
		}
	}

	/**
	 * @testdox The settings page is added as a submenu of the plugin dashboard.
	 *
	 * @return void
	 */
	public function test_the_page_is_added_under_the_dashboard_menu(): void {
		wp_set_current_user( \WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'administrator' ) ) );

		( new Settings_Page() )->register_page();

		$slugs = wp_list_pluck( $GLOBALS['submenu'][ Dashboard_Page::DASHBOARD_SLUG ] ?? array(), 2 );

		$this->assertContains( Settings_Page::PAGE_SLUG, $slugs );
	}

	/**
	 * The HTML for one of the two archive.org key fields.
	 *
	 * @param string $method The render method to call.
	 *
	 * @return string
	 */
	private function render_key_field( string $method ): string {
		ob_start();
		( new Settings_Page() )->{$method}();
		return (string) ob_get_clean();
	}

	/**
	 * Run a value through the sanitize callback registered for an option.
	 *
	 * @param string $option The option name.
	 * @param string $value  The submitted value.
	 *
	 * @return string
	 */
	private function sanitize_submitted( string $option, string $value ): string {
		$this->register_on_settings_screen();

		return (string) apply_filters( "sanitize_option_{$option}", $value, $option, $value );
	}

	/**
	 * @testdox The settings page must never put the archive.org keys into the page source. (S046)
	 *
	 * @return void
	 */
	public function test_archive_keys_are_not_rendered_into_the_page_source(): void {
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, 'real-access-key' );
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'real-secret-key' );

		$access = $this->render_key_field( 'render_archive_api_access_key' );
		$secret = $this->render_key_field( 'render_archive_api_secret_key' );

		$this->assertStringNotContainsString( 'real-access-key', $access, 'The access key is in the page source.' );
		$this->assertStringNotContainsString( 'real-secret-key', $secret, 'The secret key is in the page source.' );
	}

	/**
	 * @testdox Saving the settings page without touching a key field keeps the stored key. (S046)
	 *
	 * @return void
	 */
	public function test_resubmitting_the_masked_placeholder_keeps_the_stored_key(): void {
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'real-secret-key' );

		$this->assertSame(
			'real-secret-key',
			$this->sanitize_submitted( Settings::ARCHIVE_ORG_SECRET_KEY, Settings_Page::MASKED_KEY_PLACEHOLDER ),
			'Submitting the mask back must not overwrite the stored key.'
		);
	}

	/**
	 * @testdox Typing a new key over the mask still replaces the stored key.
	 *
	 * @return void
	 */
	public function test_a_newly_typed_key_replaces_the_stored_one(): void {
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'real-secret-key' );

		$this->assertSame(
			'a-new-secret-key',
			$this->sanitize_submitted( Settings::ARCHIVE_ORG_SECRET_KEY, 'a-new-secret-key' )
		);
	}

	/**
	 * @testdox Emptying a key field still clears the stored key.
	 *
	 * @return void
	 */
	public function test_an_emptied_key_field_still_clears_the_stored_key(): void {
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'real-secret-key' );

		$this->assertSame( '', $this->sanitize_submitted( Settings::ARCHIVE_ORG_SECRET_KEY, '' ) );
	}

	/**
	 * Put the request into the exact shape validate_archive_org_keys() looks for.
	 *
	 * @return void
	 */
	private function request_looks_like_a_saved_settings_page(): void {
		update_option( Settings::ARCHIVE_ORG_ACCESS_KEY, 'access-key' );
		update_option( Settings::ARCHIVE_ORG_SECRET_KEY, 'secret-key' );
		delete_option( Settings::ARCHIVE_ORG_CREDS_VALID_KEY );

		$_GET['page']             = Settings_Page::PAGE_SLUG;
		$_GET['settings-updated'] = 'true';
	}

	/**
	 * @testdox An anonymous request must never make validate_archive_org_keys() call out with the site credentials. (S016)
	 *
	 * admin-ajax.php fires admin_init before its own is_user_logged_in() check, so
	 * /wp-admin/admin-ajax.php?action=x&page=iawmlf_settings&settings-updated=true
	 * reaches this handler with nobody logged in.
	 *
	 * @return void
	 */
	public function test_archive_key_validation_is_not_reachable_by_an_anonymous_request(): void {
		wp_set_current_user( 0 );

		$this->request_looks_like_a_saved_settings_page();

		$client = $this->createMock( System_Client::class );
		$client->expects( $this->never() )->method( 'is_valid_user' );

		add_filter( 'iawmlf_system_client', fn() => $client );

		( new Settings_Page() )->validate_archive_org_keys();

		$this->assertFalse(
			get_option( Settings::ARCHIVE_ORG_CREDS_VALID_KEY, false ),
			'An anonymous request must not be able to write the credentials validity option.'
		);
	}

	/**
	 * @testdox A subscriber must not be able to trigger archive key validation either. (S016)
	 *
	 * @return void
	 */
	public function test_archive_key_validation_is_not_reachable_by_a_subscriber(): void {
		wp_set_current_user( \WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->request_looks_like_a_saved_settings_page();

		$client = $this->createMock( System_Client::class );
		$client->expects( $this->never() )->method( 'is_valid_user' );

		add_filter( 'iawmlf_system_client', fn() => $client );

		( new Settings_Page() )->validate_archive_org_keys();

		$this->assertFalse(
			get_option( Settings::ARCHIVE_ORG_CREDS_VALID_KEY, false ),
			'A subscriber must not be able to write the credentials validity option.'
		);
	}

	/**
	 * @testdox An administrator saving the settings page still gets the keys validated.
	 *
	 * @return void
	 */
	public function test_archive_key_validation_still_runs_for_an_administrator(): void {
		wp_set_current_user( \WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->request_looks_like_a_saved_settings_page();

		$client = $this->createMock( System_Client::class );
		$client->expects( $this->once() )
			->method( 'is_valid_user' )
			->with( 'access-key', 'secret-key' )
			->willReturn( true );

		add_filter( 'iawmlf_system_client', fn() => $client );

		( new Settings_Page() )->validate_archive_org_keys();

		$this->assertTrue( Settings::has_valid_archive_api_credentials() );
	}
}
