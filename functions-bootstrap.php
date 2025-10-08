<?php

/**
 * Handles all bootstrap functionality.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;


/**
 * Checks compatibility with the current WordPress version.
 *
 * @param   string $min_wp_version The minimum WP version required to run.
 *
 * @return  boolean
 */
function iawmlf_is_wp_version_compatible( $min_wp_version ) {
	if ( ! function_exists( 'is_wp_version_compatible' ) ) {
		return false;
	}

	return is_wp_version_compatible( $min_wp_version );
}

/**
 * Checks compatibility with the current PHP version.
 *
 * @param   string $min_php_version The minimum PHP version required to run.
 *
 * @return  boolean
 */
function iawmlf_is_php_version_compatible( $min_php_version ) {
	if ( ! function_exists( 'is_php_version_compatible' ) ) {
		return false;
	}

	return is_php_version_compatible( $min_php_version );
}

/**
 * Validates the plugin requirements.
 *
 * @return  true|\WP_Error
 */
function iawmlf_validate_requirements() {

	$is_php_compatible = iawmlf_is_php_version_compatible( WPCOMSP_WAYBACK_LINK_FIXER_MINIMUM_VERSIONS['php'] );
	$is_wp_compatible  = iawmlf_is_wp_version_compatible( WPCOMSP_WAYBACK_LINK_FIXER_MINIMUM_VERSIONS['wp'] );

	$wp_error = new \WP_Error();
	if ( ! $is_wp_compatible ) {
		$wp_error->add( 'plugin_wp_incompatible', '', array( 'requires_wp' => WPCOMSP_WAYBACK_LINK_FIXER_MINIMUM_VERSIONS['wp'] ) );
	}
	if ( ! $is_php_compatible ) {
		$wp_error->add( 'plugin_php_incompatible', '', array( 'requires_php' => WPCOMSP_WAYBACK_LINK_FIXER_MINIMUM_VERSIONS['php'] ) );
	}

	return $wp_error->has_errors() ? $wp_error : true;
}

/**
 * Outputs an error that the system requirements weren't met.
 *
 * @param   \WP_Error $error The error message to display.
 *
 * @return  void
 */
function iawmlf_output_requirements_error( $error ) {
	add_action(
		'admin_notices',
		static function () use ( $error ) {
			$requirements_error = wp_sprintf(
				/* translators: 1: Plugin name, 2: Plugin version */
				__( '<strong>%1$s (version %2$s)</strong> could not be initialized.', 'internet-archive-wayback-machine-link-fixer' ),
				__( 'Internet Archive Wayback Machine Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
				WPCOMSP_WAYBACK_LINK_FIXER_VERSION
			);

			if ( $error->has_errors() ) {
				$requirements_error .= ' ' . \__( 'Your environment does not meet all the system requirements listed below:', 'internet-archive-wayback-machine-link-fixer' );
				$requirements_error .= '<ul class="ul-disc">';

				foreach ( $error->get_error_codes() as $error_code ) {
					$error_data = $error->get_error_data( $error_code );
					if ( ! is_array( $error_data ) ) {
						$error_data = array();
					}

					switch ( $error_code ) {
						case 'plugin_wp_incompatible':
							$error_message = wp_sprintf(
								/* translators: 1: Current WP version, 2: Minimum WP version */
								__( 'Current <em>WordPress version (%1$s)</em> does not meet the minimum required version of %2$s.', 'internet-archive-wayback-machine-link-fixer' ),
								get_bloginfo( 'version' ),
								$error_data['requires_wp']
							);
							break;
						case 'plugin_php_incompatible':
							$error_message = wp_sprintf(
								/* translators: 1: Current PHP version, 2: Minimum PHP version */
								__( 'Current <em>PHP version (%1$s)</em> does not meet the minimum required version of %2$s.', 'internet-archive-wayback-machine-link-fixer' ),
								PHP_VERSION,
								$error_data['requires_php']
							);
							break;
						case 'missing_autoloader':
							$error_message = __( 'The autoloader file is missing. Please run <code>composer install</code> to generate it.', 'internet-archive-wayback-machine-link-fixer' );
							break;
						default:
							$error_message = $error->get_error_message( $error_code );
					}

					$requirements_error .= "<li>$error_message</li>";
				}

				$requirements_error .= '</ul>';
			}

			wp_admin_notice( $requirements_error, array( 'type' => 'error' ) );
		}
	);
}
