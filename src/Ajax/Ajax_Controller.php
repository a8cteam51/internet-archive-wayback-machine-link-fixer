<?php

/**
 * Handles the regisration of all AJAX actions.
 *
 * @since 1.2.0
 */

declare( strict_types = 1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Ajax;

use Internet_Archive\Wayback_Machine_Link_Fixer\Ajax\Post_Search_Ajax;

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
		Post_Search_Ajax::register_ajax_call();
	}
}
