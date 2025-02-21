<?php

/**
 * Integrations for the plugin.
 *
 * Registered as an integration in src/Wayback_Link_Fixer.php
 *
 * @since 1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax\Ajax_Controller;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard\Dashboard_Notifications;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event_Controller;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post\WP_Post_Controller;
use WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post\WP_Post_Table_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Logical node for all integration functionalities.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Integrations {
	// region FIELDS AND CONSTANTS
	private $settings_page;
	private $post_controller;
	private $event_controller;
	private $ajax_controller;
	private $wp_post_table_controller;
	private $report_page;
	private $dashboard_notification;

	/**
	 * Creates a new instance of the integrations component.
	 */
	public function __construct() {
		$this->settings_page            = new Settings_Page();
		$this->post_controller          = new WP_Post_Controller();
		$this->event_controller         = new Event_Controller();
		$this->ajax_controller          = new Ajax_Controller();
		$this->wp_post_table_controller = new WP_Post_Table_Controller();
		$this->report_page              = new Report\Report_Page();
		$this->dashboard_notification   = new Dashboard_Notifications();
	}


	// endregion

	// region METHODS

	/**
	 * Initializes the integrations.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		$this->settings_page->initialize();
		$this->post_controller->initialize();
		$this->event_controller->initialize();
		$this->ajax_controller->initialize();
		$this->wp_post_table_controller->initialize();
		$this->report_page->initialize();
		$this->dashboard_notification->initialize();
	}

	// endregion
}
