<?php

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Plugin;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Link_Checker_Client;

// region

/**
 * Returns the plugin's main class instance.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  Plugin
 */
function wpcomsp_wayback_link_fixer_get_plugin_instance(): Plugin {
	return Plugin::get_instance();
}

/**
 * Activation hook.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  void
 */
function wpcomsp_wayback_link_fixer_activate(): void {
	Migrations::up();
}

/**
 * Uninstall hook.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  void
 */
function wpcomsp_wayback_link_fixer_deactivate(): void {
	Migrations::down();
}


/**
 * Returns the plugin's slug.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  string
 */
function wpcomsp_wayback_link_fixer_get_plugin_slug(): string {
	return sanitize_key( WPCOMSP_WAYBACK_LINK_FIXER_METADATA['TextDomain'] );
}

/**
 * Escape a HTTP status code.
 *
 * @since 1.0.0
 *
 * @param integer|string $code The status code.
 *
 * @return integer|null
 */
function wpcomsp_wayback_link_fixer_escape_http_status_code( $code ): ?int {
	$code = absint( (int) $code );
	return $code > 0 ? $code : null;
}

/**
 * Gets the current Snapshot Client.
 *
 * @since 1.2.0
 *
 * @return WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client
 */
function wpcomsp_wayback_link_fixer_get_snapshot_client(): Snapshot_Client {
	return apply_filters( 'wlf_snapshot_client', new HTTP_Snapshot_Client() );
}

/**
 * Gets the current Link Checker Client.
 *
 * @since 1.2.0
 *
 * @return WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client
 */
function wpcomsp_wayback_link_fixer_get_link_checker_client(): Link_Checker_Client {
	return apply_filters( 'wlf_link_checker_client', new HTTP_Link_Checker_Client() );
}

/**
 * Render a template with args.
 *
 * @since 1.0.0
 *
 * @param string               $template The file name relative to the templates directory.
 * @param array<string, mixed> $args     The arguments to pass to the template.
 * @param boolean              $render   If set to true will print, else will return as HMTL.
 *
 * @return void|string
 */
function wpcomsp_wayback_link_fixer_render_template( string $template, array $args = array(), bool $render = true ) {

	$path = WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'templates/' . $template;

	// Throw an error if the template does not exist.
	if ( ! file_exists( $path ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: template path */
				esc_html__( 'The template %s does not exist.', 'wpcomsp_wayback_link_fixer' ),
				'<code>' . esc_attr( $path ) . '</code>'
			),
			'1.0.0'
		);
		return;
	}

	// Extract the args.
	extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	// Start the output buffer.
	ob_start();

	// Include the template.
	include $path;

	// Get the contents of the buffer.
	$html = ob_get_clean();

	if ( $render ) {
		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $html ?: ''; //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
	}
}



/**
 * Enqueue select2 assets.
 *
 * @since 1.1.0
 *
 * @param array<string> $deps The dependencies.
 *
 * @return void
 */
function wpcomsp_wayback_link_fixer_enqueue_select2_assets( array $deps = array() ): void {
	wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', $deps, '4.1.0-rc.0' );
	wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0', true );
}

/**
 * Get the sites date/time format.
 *
 * @since 1.1.0
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_date_time_format(): string {
	return esc_attr( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
}

/**
 * Render the CSS used for archived links in the header.
 *
 * @since 1.1.0
 *
 * @return void
 */
function wpcomsp_wayback_link_fixer_render_archived_link_css(): void {
	$css = <<<CSS
		.wlf-archived__redirect{
			    color: var(--wp--preset--color--primary) !important;
    			padding: 0 8px;
    			font-size: 75%;
		}
		.wlf-archived__redirect:hover {
			color: #005f7b;
		}
}
CSS;

	// Filter the css.
	$css = apply_filters( 'wlf_archived_link_css', $css );

	echo '<style>' . $css . '</style>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// endregion
