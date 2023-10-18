<?php

defined( 'ABSPATH' ) || exit;

use WPcomSpecialProjects\Scaffold\Plugin;

// region

/**
 * Returns the plugin's main class instance.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  Plugin
 */
function wpcomsp_scaffold_get_plugin_instance(): Plugin {
	return Plugin::get_instance();
}

/**
 * Returns the plugin's slug.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  string
 */
function wpcomsp_scaffold_get_plugin_slug(): string {
	return sanitize_key( WPCOMSP_SCAFFOLD_METADATA['TextDomain'] );
}

// endregion

//region OTHERS

require WPCOMSP_SCAFFOLD_PATH . 'includes/assets.php';
require WPCOMSP_SCAFFOLD_PATH . 'includes/settings.php';

// endregion
