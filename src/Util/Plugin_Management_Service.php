<?php

/**
 * Class used to handle any plugin management service tasks.
 *
 * @since 1.3.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Util
 */
declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Util;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard\Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin_Management_Service
 */
class Plugin_Management_Service {

	/**
	 * Initialize the plugin management service.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'plugin_action_links_' . IAWMLF_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add the settings link to the plugin action links.
	 *
	 * @param array $links The existing links.
	 *
	 * @return array The modified links.
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = '<a href="' . esc_url( Settings_Page::get_page_url() ) . '">' . esc_html__( 'Settings', 'internet-archive-wayback-machine-link-fixer' ) . '</a>';
		$links[]       = $settings_link;
		return $links;
	}
}
