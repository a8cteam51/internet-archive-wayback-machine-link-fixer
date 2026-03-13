<?php

/**
 * Handles the registration of all REST API routes.
 *
 * @since 2.0.0
 */

declare( strict_types = 1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Rest;

use Internet_Archive\Wayback_Machine_Link_Fixer\Rest\Link_Check_Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Rest_Controller
 */
class Rest_Controller {

	/**
	 * Initializes the REST API routes.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers all REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$link_check = new Link_Check_Rest();
		$link_check->register_routes();
	}
}
