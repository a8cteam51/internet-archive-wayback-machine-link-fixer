<?php

namespace WPCOMSpecialProjects\Wayback_Link_Fixer;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class Plugin {
	// region FIELDS AND CONSTANTS


	/**
	 * The integrations component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     Integrations|null
	 */
	public ?Integrations $integrations = null;

	// endregion

	// region MAGIC METHODS

	/**
	 * Plugin constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {
		/* Empty on purpose. */
	}

	/**
	 * Prevent cloning.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	private function __clone() {
		/* Empty on purpose. */
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function __wakeup() {
		/* Empty on purpose. */
	}

	// endregion

	// region METHODS

	/**
	 * Returns the singleton instance of the plugin.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  Plugin
	 */
	public static function get_instance(): self {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Returns true if all the plugin's dependencies are met.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  boolean
	 */
	public function is_active(): bool {

		// Custom requirements check out, just ensure basic requirements are met.
		return true === WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS;
	}

	/**
	 * Initializes the plugin components.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function initialize(): void {

		$this->integrations = new Integrations();
		$this->integrations->initialize();
	}

	// endregion

	// region HOOKS

	/**
	 * Initializes the plugin components if WooCommerce is activated.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function maybe_initialize(): void {
		if ( ! $this->is_active() ) {
			add_action(
				'admin_notices',
				static function () use ( $minimum_wc_version ) {
					if ( \is_null( $minimum_wc_version ) ) {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce. Please install and/or activate WooCommerce!', 'wpcomsp_wayback_link_fixer' ),
							WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Name'],
							WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
						);
					} else {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version, 3. Minimum WC version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce %3$s or newer. Please install, update, and/or activate WooCommerce!', 'wpcomsp_wayback_link_fixer' ),
							WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Name'],
							WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
							$minimum_wc_version
						);
					}

					$html_message = \wp_sprintf( '<div class="error notice wpcomsp-wayback-link-fixer-error">%s</div>', wpautop( $message ) );
					echo \wp_kses_post( $html_message );
				}
			);
			return;
		}

		$this->initialize();
	}

	// endregion
}
