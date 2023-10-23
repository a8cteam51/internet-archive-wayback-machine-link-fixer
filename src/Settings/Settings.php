<?php

/**
 * The Settings access class.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings
 */
class Settings {

	public const POST_TYPES_OPTION_KEY = 'wpcomsp_wayback_link_fixer_post_types';

	/**
	 * Get all post types which should be scanned.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  string[]
	 */
	public static function get_post_types(): array {
		return array_map( 'esc_html', (array) get_option( self::POST_TYPES_OPTION_KEY, array( 'page', 'post' ) ) );
	}
}
