<?php

/**
 * The reporting capability must be read when the hook fires, not at registration. (S049)
 *
 * Dashboard_Page, Dashboard_Notifications and WP_Post_Table_Controller are all
 * constructed and initialized on plugins_loaded. A site that widens access with
 * the documented iawmlf_reporting_page_capability filter does so on init, which
 * is later. If the capability is read once at registration time the filter is
 * silently ignored and those users never see the dashboard, the widget or the
 * post list column.
 *
 * Each test below reproduces that real ordering: initialize (plugins_loaded),
 * then filter (init), then fire the hook.
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests;

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Notifications;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Table_Controller;

/**
 * Test_Reporting_Capability_Timing
 *
 * @group Capabilities
 */
class Test_Reporting_Capability_Timing extends \WP_UnitTestCase {

	/**
	 * Globals the admin menu and dashboard widgets are registered into.
	 *
	 * @var array<string, mixed>
	 */
	private $globals = array();

	public function set_up(): void {
		parent::set_up();

		foreach ( array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', 'wp_meta_boxes' ) as $key ) {
			$this->globals[ $key ] = $GLOBALS[ $key ] ?? null;
		}

		if ( ! function_exists( 'add_menu_page' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
			require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		}

		// An editor: no manage_options, so only the filter can let them through.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
	}

	public function tear_down(): void {
		foreach ( $this->globals as $key => $value ) {
			$GLOBALS[ $key ] = $value;
		}

		remove_all_filters( 'iawmlf_reporting_page_capability' );

		parent::tear_down();
	}

	/**
	 * Widen the reporting capability the way a site owner would, on init.
	 *
	 * @return void
	 */
	private function widen_capability_on_init(): void {
		add_filter(
			'iawmlf_reporting_page_capability',
			function (): string {
				return 'edit_posts';
			}
		);
	}

	/**
	 * @testdox An init-time capability filter must still open the dashboard menu. (S049)
	 *
	 * @return void
	 */
	public function test_dashboard_menu_honours_a_capability_filter_added_after_registration(): void {
		// plugins_loaded.
		( new Dashboard_Page() )->initialize();

		// init.
		$this->widen_capability_on_init();

		// admin_menu.
		$GLOBALS['menu']             = array();
		$GLOBALS['admin_page_hooks'] = array();
		do_action( 'admin_menu' );

		$this->assertArrayHasKey(
			Dashboard_Page::DASHBOARD_SLUG,
			$GLOBALS['admin_page_hooks'],
			'The dashboard menu was never registered, so the init-time capability filter was ignored.'
		);
	}

	/**
	 * @testdox An init-time capability filter must still add the dashboard widget. (S049)
	 *
	 * @return void
	 */
	public function test_dashboard_widget_honours_a_capability_filter_added_after_registration(): void {
		// plugins_loaded.
		( new Dashboard_Notifications() )->initialize();

		// init.
		$this->widen_capability_on_init();

		// wp_dashboard_setup. wp_add_dashboard_widget() registers against the current
		// screen, and silently does nothing without one, so set it as the dashboard does.
		set_current_screen( 'dashboard' );

		$GLOBALS['wp_meta_boxes'] = array();
		do_action( 'wp_dashboard_setup' );

		$widgets = $GLOBALS['wp_meta_boxes']['dashboard']['normal']['high'] ?? array();

		$this->assertArrayHasKey(
			'iawmlf_dashboard_widget',
			$widgets,
			'The dashboard widget was never registered, so the init-time capability filter was ignored.'
		);
	}

	/**
	 * @testdox An init-time capability filter must still add the post list link column. (S049)
	 *
	 * @return void
	 */
	public function test_post_list_column_honours_a_capability_filter_added_after_registration(): void {
		// plugins_loaded.
		( new WP_Post_Table_Controller() )->initialize();

		// init.
		$this->widen_capability_on_init();

		// manage_posts_columns.
		$columns = apply_filters( 'manage_posts_columns', array() );

		$this->assertArrayHasKey(
			WP_Post_Table_Controller::LINK_COLUMN_KEY,
			$columns,
			'The links column was never added, so the init-time capability filter was ignored.'
		);
	}

	/**
	 * The submenu slugs registered under the plugin's dashboard menu.
	 *
	 * @return array<string>
	 */
	private function dashboard_submenu_slugs(): array {
		$GLOBALS['menu']             = array();
		$GLOBALS['submenu']          = array();
		$GLOBALS['admin_page_hooks'] = array();

		do_action( 'admin_menu' );

		return wp_list_pluck( $GLOBALS['submenu'][ Dashboard_Page::DASHBOARD_SLUG ] ?? array(), 2 );
	}

	/**
	 * @testdox The links report page must follow the reporting capability, not a hardcoded one. (S122)
	 *
	 * Dashboard_Page and WP_Post_Table_Controller gate on the filterable capability,
	 * so widening it opens the dashboard and the post column but left the report
	 * page itself shut.
	 *
	 * @return void
	 */
	public function test_the_report_page_follows_the_reporting_capability(): void {
		( new Dashboard_Page() )->initialize();
		( new Report_Page() )->initialize();

		$this->widen_capability_on_init();

		// Matches Report_Page::SLUG, which is private.
		$this->assertContains(
			'iawmlf-links',
			$this->dashboard_submenu_slugs(),
			'Widening the reporting capability did not open the links report page.'
		);
	}

	/**
	 * @testdox Without the filter the report page stays closed to an editor. (S122)
	 *
	 * @return void
	 */
	public function test_the_report_page_stays_closed_without_the_filter(): void {
		( new Dashboard_Page() )->initialize();
		( new Report_Page() )->initialize();

		$this->assertNotContains(
			'iawmlf-links',
			$this->dashboard_submenu_slugs(),
			'An editor reached the report page without the capability being widened.'
		);
	}

	/**
	 * @testdox The settings page must stay on manage_options, it holds the archive.org keys. (S122)
	 *
	 * Widening the reporting capability so editors can read link reports must never
	 * hand them the API credentials screen.
	 *
	 * @return void
	 */
	public function test_the_settings_page_does_not_follow_the_reporting_capability(): void {
		( new Dashboard_Page() )->initialize();
		( new Settings_Page() )->initialize();

		$this->widen_capability_on_init();

		$this->assertNotContains(
			Settings_Page::PAGE_SLUG,
			$this->dashboard_submenu_slugs(),
			'Widening the reporting capability exposed the archive.org credentials page.'
		);
	}

	/**
	 * @testdox A user who passes no reporting capability still gets nothing.
	 *
	 * @return void
	 */
	public function test_a_user_without_the_capability_still_gets_no_column(): void {
		// No filter this time, so an editor cannot meet the default manage_options.
		( new WP_Post_Table_Controller() )->initialize();

		$columns = apply_filters( 'manage_posts_columns', array() );

		$this->assertArrayNotHasKey(
			WP_Post_Table_Controller::LINK_COLUMN_KEY,
			$columns,
			'An editor must not get the links column when the capability was never widened.'
		);
	}
}
