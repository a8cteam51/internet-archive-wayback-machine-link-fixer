<?php

namespace WPCOMSpecialProjects\Wayback_Link_Fixer;

use WPCOMSpecialProjects\Wayback_Link_Fixer\CLI\Commands;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Events;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer\Report_Viewer_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Logical node for all integration functionalities.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Integrations {
	// region FIELDS AND CONSTANTS
	private Settings_Page $settings_page;
	private Report_Viewer_Page $report_viewer_page;
	private Event_Page $event_page;
	private Events $events;
	private Commands $commands;

	/**
	 * Creates a new instance of the integrations component.
	 */
	public function __construct() {
		$this->settings_page      = new Settings_Page();
		$this->report_viewer_page = new Report_Viewer_Page();
		$this->event_page         = new Event_Page();
		$this->events             = new Events();
		$this->commands           = new Commands();
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
		$this->report_viewer_page->initialize();
		$this->event_page->initialize();
		$this->events->initialize();
		$this->commands->initialize();
	}

	// endregion
}
