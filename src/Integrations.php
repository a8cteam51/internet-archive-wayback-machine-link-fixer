<?php

/**
 * Integrations for the plugin.
 *
 * Registered as an integration in src/Wayback_Link_Fixer.php
 *
 * @since 1.0.0
 */

namespace Internet_Archive\Wayback_Machine_Link_Fixer;

use Internet_Archive\Wayback_Machine_Link_Fixer\Ajax\Ajax_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Notifications;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Event_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Table_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Setup_Wizard;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page;


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
	private $setup_wizard;
	private $dashboard_page;
	private $plugin_management;

	/**
	 * Creates a new instance of the integrations component.
	 */
	public function __construct() {
		$this->settings_page            = new Settings_Page();
		$this->post_controller          = new WP_Post_Controller();
		$this->event_controller         = new Event_Controller();
		$this->ajax_controller          = new Ajax_Controller();
		$this->wp_post_table_controller = new WP_Post_Table_Controller();
		$this->report_page              = new Report_Page();
		$this->dashboard_notification   = new Dashboard_Notifications();
		$this->setup_wizard             = new Setup_Wizard();
		$this->dashboard_page           = new Dashboard_Page();
		$this->plugin_management        = new Util\Plugin_Management_Service();
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
		$this->dashboard_page->initialize();
		$this->settings_page->initialize();
		$this->post_controller->initialize();
		$this->event_controller->initialize();
		$this->ajax_controller->initialize();
		$this->wp_post_table_controller->initialize();
		$this->report_page->initialize();
		$this->dashboard_notification->initialize();
		$this->setup_wizard->initialize();
		$this->plugin_management->initialize();
	}

	// endregion
}
