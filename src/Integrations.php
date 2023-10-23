<?php

namespace WPCOMSpecialProjects\Wayback_Link_Fixer;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;

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

	/**
	 * Creates a new instance of the integrations component.
	 */
	public function __construct() {
		$this->settings_page = new Settings_Page();
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
	}

	// endregion
}
