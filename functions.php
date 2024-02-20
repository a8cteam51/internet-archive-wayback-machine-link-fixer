<?php

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Plugin;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event_Page;
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
 * Get all HTTP codes.
 *
 * @since 1.0.0
 *
 * @return array<int, int>
 */
function wpcomsp_wayback_link_fixer_get_http_codes(): array {
	return array(
		100,
		101,
		200,
		201,
		202,
		203,
		204,
		205,
		206,
		300,
		301,
		302,
		303,
		304,
		305,
		400,
		401,
		402,
		403,
		404,
		405,
		406,
		407,
		408,
		409,
		410,
		411,
		412,
		413,
		414,
		415,
		500,
		501,
		502,
		503,
		504,
		505,
	);
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

/**
 * Generates the title with link for a post as part pf a log.
 *
 * @param integer $post_id The post id.
 * @param integer $blog_id The blog id.
 *
 * @return string The title with link.
 */
function wpcomsp_wayback_link_fixer_get_log_post_title( int $post_id, int $blog_id ): string {

	// Cache the current blog id.
	$current_blog_id = get_current_blog_id();

	// If a multisite, swtich to the blog.
	if ( is_multisite() && $blog_id !== $current_blog_id ) {
		switch_to_blog( $blog_id );
	}
	// If the post doesnt exist
	if ( ! get_post_status( $post_id ) ) {
		return esc_html__( 'Post Not Found', 'wpcomsp_wayback_link_fixer' );
	}

	$wlf_log_post_title = get_the_title( $post_id );
	$link               = sprintf(
		// translators: %1$s is the post url, %2$s is the post title, %3$d is the post id.
		"<a href='%s'>%s (#%d)</a>",
		esc_url( get_edit_post_link( $post_id ) ?? '#' ),
		'' === $wlf_log_post_title ? esc_html__( 'No title', 'wpcomsp_wayback_link_fixer' ) : esc_html( $wlf_log_post_title ),
		$post_id
	);

	// If a multisite, swtich back to the current blog.
	if ( is_multisite() && $blog_id !== $current_blog_id ) {
		switch_to_blog( $current_blog_id );
	}

	return $link;
}

/**
 * Renders an admin notice.
 *
 * @since 1.0.0
 *
 * @param string $message The message to display.
 * @param string $type    The type of notice. Can be error, warning, success or info.
 *
 * @return void
 */
function wpcomsp_wayback_link_fixer_render_admin_notice( string $message, string $type = 'error' ): void {
	add_action(
		'admin_notices',
		function () use ( $message, $type ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				esc_html( $message )
			);
		}
	);
}

/**
 * Get the name of a user for the select2 field.
 *
 * @since 1.0.0
 *
 * @param \WP_User $user The user.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_user_name( WP_User $user ): string {
	// Get the name.
	$name = $user->display_name;

	// If we have no name, show the username.
	if ( ! $name ) {
		$name = $user->user_login;
	}

	// If we have no username, show the email.
	if ( ! $name ) {
		$name = $user->user_email;
	}

	return $name;
}

/**
 * Get the name of a blog based on its id.
 *
 * @since 1.0.0
 *
 * @param integer $blog_id The blog id.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_blog_name( int $blog_id ): string {
	return get_blog_option( $blog_id, 'blogname' );
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
 * Generates the link for a new report.
 *
 * Takes into account if network admin or not.
 *
 * @since 1.1.0
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_new_report_link(): string {
	$wlf_page_slug = Event_Page::PAGE_SLUG;

	if ( is_network_admin() ) {
		return admin_url( "network/admin.php?page={$wlf_page_slug}&action=new" );
	}
	return admin_url( "admin.php?page={$wlf_page_slug}&action=new" );
}

// endregion

//region OTHERS

require WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'includes/assets.php';
require WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'includes/settings.php';

// endregion
