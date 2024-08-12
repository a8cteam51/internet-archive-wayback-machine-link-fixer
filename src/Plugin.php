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
		return is_bool( WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS ) // @phpstan-ignore-line
		&& true === WPCOMSP_WAYBACK_LINK_FIXER_REQUIREMENTS;
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
			return;
		}

		$this->initialize();
	}

	// endregion
}
