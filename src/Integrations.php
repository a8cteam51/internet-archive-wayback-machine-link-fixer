<?php

namespace WPCOMSpecialProjects\Wayback_Link_Fixer;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer\Runner\Meta_Box_Runner;
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
	private Meta_Box_Runner $meta_box_runner;

	/**
	 * Creates a new instance of the integrations component.
	 */
	public function __construct() {
		$this->settings_page      = new Settings_Page();
		$this->report_viewer_page = new Report_Viewer_Page();
		$this->meta_box_runner    = new Meta_Box_Runner();
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
		$this->meta_box_runner->initialize();
	}

	// endregion
}
