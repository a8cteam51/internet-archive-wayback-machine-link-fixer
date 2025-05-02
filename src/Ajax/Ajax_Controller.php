<?php

/**
 * Handles the regisration of all AJAX actions.
 *
 * @since 1.2.0
 */

declare( strict_types = 1 );

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax\Link_Check_Ajax;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax_Controller
 */
class Ajax_Controller {

	/**
	 * Initializes the AJAX actions.
	 *
	 * @return void
	 */
	public function initialize(): void {
		Link_Check_Ajax::register_ajax_call();
	}
}
