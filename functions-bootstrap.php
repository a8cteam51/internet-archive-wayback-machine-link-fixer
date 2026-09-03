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
 * Parses a database server version string into its flavour and version.
 *
 * The version is empty when it cannot be determined.
 *
 * @param   string $server_info The raw server version string.
 *
 * @return  array{flavour:string, version:string}
 */
function iawmlf_parse_database_version( $server_info ) {
	$server_info = (string) $server_info;
	$is_mariadb  = false !== stripos( $server_info, 'mariadb' );

	// MariaDB reports a fake "5.5.5-" prefix for legacy client compatibility.
	if ( $is_mariadb && 0 === strpos( $server_info, '5.5.5-' ) ) {
		$server_info = substr( $server_info, 6 );
	}

	preg_match( '/^\d+(?:\.\d+)*/', $server_info, $matches );

	return array(
		'flavour' => $is_mariadb ? 'mariadb' : 'mysql',
		'version' => isset( $matches[0] ) ? $matches[0] : '',
	);
}

/**
 * Gets the database server flavour and version.
 *
 * @return  array{flavour:string, version:string}
 */
function iawmlf_get_database_version() {
	global $wpdb;

	return iawmlf_parse_database_version( $wpdb instanceof \wpdb ? $wpdb->db_server_info() : '' );
}

/**
 * Checks the database supports the JSON column type used by Migration_1.
 *
 * Assumes compatible when the version cannot be determined.
 *
 * @param   array|null $database Parsed database details, read from the connection when null.
 *
 * @return  boolean
 */
function iawmlf_is_database_version_compatible( $database = null ) {
	$database = is_array( $database ) ? $database : iawmlf_get_database_version();

	if ( '' === $database['version'] ) {
		return true;
	}

	return version_compare( $database['version'], IAWMLF_MINIMUM_VERSIONS[ $database['flavour'] ], '>=' );
}

/**
 * Validates the plugin requirements.
 *
 * @return  true|\WP_Error
 */
function iawmlf_validate_requirements() {

	$is_php_compatible      = iawmlf_is_php_version_compatible( IAWMLF_MINIMUM_VERSIONS['php'] );
	$is_wp_compatible       = iawmlf_is_wp_version_compatible( IAWMLF_MINIMUM_VERSIONS['wp'] );
	$is_database_compatible = iawmlf_is_database_version_compatible();

	$wp_error = new \WP_Error();
	if ( ! $is_wp_compatible ) {
		$wp_error->add( 'plugin_wp_incompatible', '', array( 'requires_wp' => IAWMLF_MINIMUM_VERSIONS['wp'] ) );
	}
	if ( ! $is_php_compatible ) {
		$wp_error->add( 'plugin_php_incompatible', '', array( 'requires_php' => IAWMLF_MINIMUM_VERSIONS['php'] ) );
	}
	if ( ! $is_database_compatible ) {
		$database = iawmlf_get_database_version();
		$wp_error->add(
			'plugin_db_incompatible',
			'',
			array(
				'database_name'    => 'mariadb' === $database['flavour'] ? 'MariaDB' : 'MySQL',
				'database_version' => $database['version'],
				'requires_db'      => IAWMLF_MINIMUM_VERSIONS[ $database['flavour'] ],
			)
		);
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
				IAWMLF_VERSION
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
						case 'plugin_db_incompatible':
							$error_message = wp_sprintf(
								/* translators: 1: Database name, 2: Current database version, 3: Minimum database version */
								__( 'Current <em>%1$s version (%2$s)</em> does not meet the minimum required version of %3$s, which is needed for JSON column support.', 'internet-archive-wayback-machine-link-fixer' ),
								$error_data['database_name'],
								$error_data['database_version'],
								$error_data['requires_db']
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

			// wp_admin_notice() only exists from WP 6.4, the version this notice may be reporting as missing.
			if ( function_exists( 'wp_admin_notice' ) ) {
				wp_admin_notice( $requirements_error, array( 'type' => 'error' ) );
			} else {
				echo '<div class="notice notice-error"><p>' . wp_kses_post( $requirements_error ) . '</p></div>';
			}
		}
	);
}
