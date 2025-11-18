<?php

/**
 * Class used to handle environmental utility tasks.
 *
 * @since 1.3.3
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Util
 */

declare( strict_types=1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Util;

defined( 'ABSPATH' ) || exit;

/**
 * Environmental
 */
class Environmental {

	/**
	 * Checks if the current environment is production.
	 *
	 * @return boolean True if production environment, false otherwise.
	 */
	public static function is_production(): bool {

		$is_production = false;

		// If get environment_type function exists and returns 'production', consider it a production environment.
		if ( function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() === 'production' ) {
			$is_production = true;
		}

		return apply_filters( 'iawmlf_is_production_environment', $is_production );
	}
}
