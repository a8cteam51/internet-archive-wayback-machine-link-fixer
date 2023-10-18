<?php

namespace WPCOMSpecialProjects\Scaffold\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the integration with WooCommerce Subscriptions.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class WC_Subscriptions {
	// region METHODS

	/**
	 * Returns true if the integration is active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  boolean
	 */
	public function is_active(): bool {
		return \class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Initializes the integration if it's active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function maybe_initialize(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		$this->initialize();
	}

	/**
	 * Initializes the integration.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function initialize(): void {
		// HOOKS AND FILTERS HERE
	}

	// endregion

	// region HOOKS

	// ADD HOOK AND FILTER METHODS HERE

	// endregion
}
