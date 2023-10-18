<?php

namespace WPCOMSpecialProjects\Scaffold;

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
	 * The blocks component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     Blocks|null
	 */
	public ?Blocks $blocks = null;

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
	 * @param   string|null $minimum_wc_version The minimum WooCommerce version required.
	 *
	 * @return  boolean
	 */
	public function is_active( string &$minimum_wc_version = null ): bool {
		// Check if WooCommerce is active.
		$woocommerce_exists = \class_exists( 'WooCommerce' ) && \defined( 'WC_VERSION' );
		if ( ! $woocommerce_exists ) {
			return false;
		}

		// Get the minimum WooCommerce version required from the plugin's header, if needed.
		if ( null === $minimum_wc_version ) {
			$updated_plugin_metadata = \get_plugin_data( \trailingslashit( WP_PLUGIN_DIR ) . WPCOMSP_SCAFFOLD_BASENAME, false, false );
			if ( ! \array_key_exists( \WC_Plugin_Updates::VERSION_REQUIRED_HEADER, $updated_plugin_metadata ) ) {
				return false;
			}

			$minimum_wc_version = $updated_plugin_metadata[ \WC_Plugin_Updates::VERSION_REQUIRED_HEADER ];
		}

		// Check if WooCommerce version is supported.
		$woocommerce_supported = \version_compare( WC_VERSION, $minimum_wc_version, '>=' );
		if ( ! $woocommerce_supported ) {
			return false;
		}

		// Custom requirements check out, just ensure basic requirements are met.
		return true === WPCOMSP_SCAFFOLD_REQUIREMENTS;
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
		$this->blocks = new Blocks();
		$this->blocks->initialize();

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
		if ( ! $this->is_active( $minimum_wc_version ) ) {
			add_action(
				'admin_notices',
				static function() use ( $minimum_wc_version ) {
					if ( \is_null( $minimum_wc_version ) ) {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce. Please install and/or activate WooCommerce!', 'wpcomsp-scaffold' ),
							WPCOMSP_SCAFFOLD_METADATA['Name'],
							WPCOMSP_SCAFFOLD_METADATA['Version']
						);
					} else {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version, 3. Minimum WC version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce %3$s or newer. Please install, update, and/or activate WooCommerce!', 'wpcomsp-scaffold' ),
							WPCOMSP_SCAFFOLD_METADATA['Name'],
							WPCOMSP_SCAFFOLD_METADATA['Version'],
							$minimum_wc_version
						);
					}

					$html_message = \wp_sprintf( '<div class="error notice wpcomsp-scaffold-error">%s</div>', wpautop( $message ) );
					echo \wp_kses_post( $html_message );
				}
			);
			return;
		}

		$this->initialize();
	}

	// endregion
}
