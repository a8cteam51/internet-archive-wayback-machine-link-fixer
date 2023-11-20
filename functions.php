<?php

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Plugin;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations;

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
 * Get the user name of the report creator.
 *
 * @since 1.0.0
 *
 * @param \WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report $report The report.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_report_author( Report $report ): string {
	$user_id = $report->get_user_id();
	$user    = get_userdata( $user_id );
	return $user && $user->display_name ? $user->display_name : __( 'User not found', 'wpcomsp_wayback_link_fixer' );
}

/**
 * Get the name of a staus code.
 *
 * @since 1.0.0
 *
 * @param integer $code The status code.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_status_code_name( int $code ): string {
	switch ( $code ) {
		case 100:
			$text = __( 'Continue', 'wpcomsp_wayback_link_fixer' );
			break;
		case 101:
			$text = __( 'Switching Protocols', 'wpcomsp_wayback_link_fixer' );
			break;
		case 200:
			$text = __( 'OK', 'wpcomsp_wayback_link_fixer' );
			break;
		case 201:
			$text = __( 'Created', 'wpcomsp_wayback_link_fixer' );
			break;
		case 202:
			$text = __( 'Accepted', 'wpcomsp_wayback_link_fixer' );
			break;
		case 203:
			$text = __( 'Non-Authoritative Information', 'wpcomsp_wayback_link_fixer' );
			break;
		case 204:
			$text = __( 'No Content', 'wpcomsp_wayback_link_fixer' );
			break;
		case 205:
			$text = __( 'Reset Content', 'wpcomsp_wayback_link_fixer' );
			break;
		case 206:
			$text = __( 'Partial Content', 'wpcomsp_wayback_link_fixer' );
			break;
		case 300:
			$text = __( 'Multiple Choices', 'wpcomsp_wayback_link_fixer' );
			break;
		case 301:
			$text = __( 'Moved Permanently', 'wpcomsp_wayback_link_fixer' );
			break;
		case 302:
			$text = __( 'Moved Temporarily', 'wpcomsp_wayback_link_fixer' );
			break;
		case 303:
			$text = __( 'See Other', 'wpcomsp_wayback_link_fixer' );
			break;
		case 304:
			$text = __( 'Not Modified', 'wpcomsp_wayback_link_fixer' );
			break;
		case 305:
			$text = __( 'Use Proxy', 'wpcomsp_wayback_link_fixer' );
			break;
		case 400:
			$text = __( 'Bad Request', 'wpcomsp_wayback_link_fixer' );
			break;
		case 401:
			$text = __( 'Unauthorized', 'wpcomsp_wayback_link_fixer' );
			break;
		case 402:
			$text = __( 'Payment Required', 'wpcomsp_wayback_link_fixer' );
			break;
		case 403:
			$text = __( 'Forbidden', 'wpcomsp_wayback_link_fixer' );
			break;
		case 404:
			$text = __( 'Not Found', 'wpcomsp_wayback_link_fixer' );
			break;
		case 405:
			$text = __( 'Method Not Allowed', 'wpcomsp_wayback_link_fixer' );
			break;
		case 406:
			$text = __( 'Not Acceptable', 'wpcomsp_wayback_link_fixer' );
			break;
		case 407:
			$text = __( 'Proxy Authentication Required', 'wpcomsp_wayback_link_fixer' );
			break;
		case 408:
			$text = __( 'Request Time-out', 'wpcomsp_wayback_link_fixer' );
			break;
		case 409:
			$text = __( 'Conflict', 'wpcomsp_wayback_link_fixer' );
			break;
		case 410:
			$text = __( 'Gone', 'wpcomsp_wayback_link_fixer' );
			break;
		case 411:
			$text = __( 'Length Required', 'wpcomsp_wayback_link_fixer' );
			break;
		case 412:
			$text = __( 'Precondition Failed', 'wpcomsp_wayback_link_fixer' );
			break;
		case 413:
			$text = __( 'Request Entity Too Large', 'wpcomsp_wayback_link_fixer' );
			break;
		case 414:
			$text = __( 'Request-URI Too Large', 'wpcomsp_wayback_link_fixer' );
			break;
		case 415:
			$text = __( 'Unsupported Media Type', 'wpcomsp_wayback_link_fixer' );
			break;
		case 500:
			$text = __( 'Internal Server Error', 'wpcomsp_wayback_link_fixer' );
			break;
		case 501:
			$text = __( 'Not Implemented', 'wpcomsp_wayback_link_fixer' );
			break;
		case 502:
			$text = __( 'Bad Gateway', 'wpcomsp_wayback_link_fixer' );
			break;
		case 503:
			$text = __( 'Service Unavailable', 'wpcomsp_wayback_link_fixer' );
			break;
		case 504:
			$text = __( 'Gateway Time-out', 'wpcomsp_wayback_link_fixer' );
			break;
		case 505:
			$text = __( 'HTTP Version not supported', 'wpcomsp_wayback_link_fixer' );
			break;
		default:
			$text = sprintf(
				// translators: %d is the status code.
				__( 'Unknown http status code "%d"', 'wpcomsp_wayback_link_fixer' ),
				absint( $code )
			);
			break;
	}
		return $text;
}

// endregion

//region OTHERS

require WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'includes/assets.php';
require WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'includes/settings.php';

// endregion
