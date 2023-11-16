<?php

/**
 * Page used to render the Reports as both a list and a single .
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * Report View Page
 */
class Report_Viewer_Page {

	public const PAGE_SLUG                 = 'wayback-link-fixer-reports';
	public const MENU_POSITION             = 91;
	public const DELETE_REPORT_AJAX_HANDLE = 'wlf_delete_report';
	public const EXPORT_REPORT_AJAX_HANDLE = 'wlf_export_report';

	/**
	 * The page hook.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private ?string $page_hook = null;

	/**
	 * Access to the Reports Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Create instance of Report_Viewer_Page.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->report_repository = new Report_Repository();
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_menu', array( $this, 'change_wp_menu_title' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Register the page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		$this->page_hook = \add_menu_page(
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			__( 'Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			'manage_options',
			self::PAGE_SLUG,
			$this->is_list_view()
				? new Report_List_View( $this->report_repository )
				: new Report_Single_View( $this->report_repository ),
			'dashicons-admin-tools',
			self::MENU_POSITION,
		);
	}

	/**
	 * Change the main menu item title.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function change_wp_menu_title(): void {
		global $menu, $submenu;

		// If our parent page is not here, bail.
		if ( ! isset( $menu[ self::MENU_POSITION ] ) ) {
			return;
		}

		// If the submenu set is missing, bail.
		if ( ! isset( $submenu[ self::PAGE_SLUG ] ) ) {
			return;
		}

		// Change the title.
		$submenu[ self::PAGE_SLUG ][0][0] = __( 'All Reports', 'wpcomsp_wayback_link_fixer' ); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Checks if we are showing the list view.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	private function is_list_view(): bool {
		return ! isset( $_GET['report_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Enqueue all admin scripts and styles for this page only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_hook The page hook.
	 *
	 * @return void
	 */
	public function enqueue_scripts( string $page_hook ): void {
		// If this is not our page, bail.
		if ( $this->page_hook !== $page_hook ) {
			return;
		}

		//  Register the styles.
		wp_enqueue_style(
			$this->page_hook,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
		);

		// Register the scripts.
		wp_register_script(
			$this->page_hook,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/report-viewer.js',
			array( 'jquery' ),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);

		// Localize the script.
		wp_localize_script(
			$this->page_hook,
			'wlf_report_viewer',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wlf_report_viewer' ),
			)
		);

		// Enqueue the script.
		wp_enqueue_script( $this->page_hook );

		// Include select2
		wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
		wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0', true );
	}

	/**
	 * Render the notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_notices(): void {
	}
}
