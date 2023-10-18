<?php

namespace WPCOMSpecialProjects\Scaffold;

use WPCOMSpecialProjects\Scaffold\Integrations\WC_Subscriptions;

defined( 'ABSPATH' ) || exit;

/**
 * Logical node for all integration functionalities.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Integrations {
	// region FIELDS AND CONSTANTS

	/**
	 * The WooCommerce Subscriptions integration instance.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     WC_Subscriptions|null
	 */
	public ?WC_Subscriptions $wc_subscriptions = null;

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
		$this->wc_subscriptions = new WC_Subscriptions();
		$this->wc_subscriptions->maybe_initialize();
	}

	// endregion
}
