<?php

/**
 * Plugin Functions.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Plugin;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings_Page;
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
 * Get the current System Client.
 *
 * @since 1.3.0
 *
 * @return WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\System_Client
 */
function wpcomsp_wayback_link_fixer_get_system_client(): \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\System_Client {
	return apply_filters( 'wlf_system_client', new \WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_System_Client() );
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

/**
 * Get image asset URL from filename.
 *
 * @since 1.2.0
 *
 * @param string $filename The filename.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_image_asset_url( string $filename ): string {
	return esc_url( WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/images/' . $filename );
}

/**
 * Trims a string based on a defined length with a suffix.
 *
 * @since 1.2.0
 *
 * @param string  $text   The text to trim.
 * @param integer $length The length to trim to.
 * @param string  $suffix The suffix to append.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_trim_string( string $text, int $length, string $suffix = '...' ): string {
	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}

	return mb_strimwidth( $text, 0, $length, $suffix );
}

/**
 * Get the sites date/time format
 *
 * @since 1.2.0
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_get_date_format(): string {
	return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
}

/**
 * Renders the notice about not authenticated with the Wayback Machine.
 *
 * @since 1.3.0
 *
 * @return void
 */
function wpcomsp_wayback_link_fixer_render_not_authenticated_notice(): void {
	$in_unauthenticated_mode = __( 'You are using Link Fixer in unauthenticated mode, which restricts you to 4000 new snapshots per day. To unlock higher limits, please enter your API credentials to authenticate with Archive.org.', 'wpcomsp_wayback_link_fixer' );

	// If the archive api is not configured.
	if ( ! Settings::is_archive_api_configured() ) {
		?>
		<div class="notice notice-error">
			<p>
				<?php echo esc_html( $in_unauthenticated_mode ); ?>
			</p>
		</div>
		<?php
		return;
	}

	// If the creds are invalid.
	if ( ! Settings::has_valid_archive_api_credentials() ) {
		$message  = __( 'Your Archive.org API credentials are invalid. Please check your settings.', 'wpcomsp_wayback_link_fixer' );
		$message .= ' ' . $in_unauthenticated_mode;
		?>
		<div class="notice notice-error">
			<p>
				<?php
				print wp_kses_post(
					sprintf(
						// translators: %s is a link to the settings page.
						__( 'Your Archive.org API credentials are invalid. <a href="%s">Please check your settings.</a>. As a result you are in in unauthenticated mode, which restricts you to 4000 new snapshots per day.', 'wpcomsp_wayback_link_fixer' ),
						esc_url( admin_url( 'options-general.php?page=' . Settings_Page::PAGE_SLUG ) )
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

/**
 * Renders the notice about the Wayback Machine being offline.
 *
 * @since 1.3.0
 *
 * @return void
 */
function wpcomsp_wayback_link_fixer_render_wayback_offline_notice(): void {
	// If the Wayback Machine is online, bail.
	if ( Settings::is_archive_api_online() ) {
		return;
	}

	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'The Wayback Machine is currently offline. Please try again later.', 'wpcomsp_wayback_link_fixer' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Checks if a link is already an internet archive link.
 *
 * @since 1.3.0
 *
 * @param string $url The URL to check.
 *
 * @return boolean
 */
function wpcomsp_wayback_link_fixer_is_archive_link( string $url ): bool {
	$urls = array(
		'https://web.archive.org/web/',
		'http://web.archive.org/web/',
	);

	foreach ( $urls as $archive_url ) {
		if ( 0 === strpos( $url, $archive_url ) ) {
			return true;
		}
	}
		return false;
}

/**
 * Checks if a link is from the current site.
 *
 * @since 1.3.0
 *
 * @param string $url The URL to check.
 *
 * @return boolean
 */
function wpcomsp_wayback_link_fixer_is_current_site_link( string $url ): bool {
	// Get the site urls with all protocols.
	$site_urls = array(
		get_site_url( null, '', 'https' ),
		get_site_url( null, '', 'http' ),
	);
	// Normalize the URL.
	$normalized_url = wpcomsp_wayback_link_fixer_normalize_url( $url );

	// Check if the URL starts with any of the site URLs.
	foreach ( $site_urls as $site_url ) {
		if ( 0 === strpos( $normalized_url, $site_url ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Normalize a URL.
 *
 * Will urldecode, remove trailing slashes, and lowercase the URL.
 *
 * @since 1.3.0
 *
 * @param string $url The URL to normalize.
 *
 * @return string
 */
function wpcomsp_wayback_link_fixer_normalize_url( string $url ): string {
	$url = rtrim( $url, '/' );

	// URL Encode the url parameters.
	$url_parts = wp_parse_url( $url );

	// If we have a pathm, encode it.
	if ( isset( $url_parts['path'] ) ) {
		// Split path by /
		$path_parts        = explode( '/', $url_parts['path'] );
		$path_parts        = array_map( 'rawurlencode', $path_parts );
		$url_parts['path'] = implode( '/', $path_parts );
	}

	// If we have a query, encode it.
	if ( isset( $url_parts['query'] ) ) {
		$url_parts['query'] = rawurlencode( $url_parts['query'] );
	}

	// If we have a fragment, encode it.
	if ( isset( $url_parts['fragment'] ) ) {
		$url_parts['fragment'] = str_replace( '%21', '!', rawurlencode( $url_parts['fragment'] ) );
	}

	// Rebuild the scheme and host
	$url  = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] . '://' : '';
	$url .= isset( $url_parts['host'] ) ? $url_parts['host'] : '';

	// Add port if specified
	if ( isset( $url_parts['port'] ) ) {
		$url .= ':' . $url_parts['port'];
	}

	// Add the path
	$url .= isset( $url_parts['path'] ) ? $url_parts['path'] : '';

	// Add the query if present
	if ( isset( $url_parts['query'] ) ) {
		$url .= '?' . $url_parts['query'];
	}

	// Add the fragment if present
	if ( isset( $url_parts['fragment'] ) ) {
		$url .= '#' . $url_parts['fragment'];
	}

	return $url;
}

/**
 * Gets an admin link to given post ttype slug.
 *
 * @since 1.3.0
 *
 * @param string $post_type The post type slug.
 * @param string $target    The target to open the link in.
 *
 * @return string|null
 */
function wpcomsp_wayback_link_fixer_get_admin_post_type_link( string $post_type, string $target = '_self' ): ?string {
	// Get the post type object.
	$post_type_object = get_post_type_object( $post_type );

	// If we don't have a post type object, bail.
	if ( ! $post_type_object ) {
		return null;
	}

	// Get the admin URL for the post type.
	$url = admin_url( 'edit.php?post_type=' . $post_type );

	// Return the link.
	return '<a href="' . esc_url( $url ) . '" target="' . esc_attr( $target ) . '">' . esc_html( $post_type_object->labels->name ) . '</a>';
}

/**
 * Checks if the API is online.
 *
 * @since 1.3.0
 *
 * @param boolean $force If set to true, will force a check of the API status, ignoring the transient.
 *
 * @return boolean
 */
function wpcomsp_wayback_link_fixer_is_archive_api_online( bool $force = false ): bool {
	// Try to get from transient.
	$online = get_transient( 'wlf_archive_api_online' );
	if ( false !== (bool) $online && false === $force ) {
		return (bool) $online;
	}

	// Check if the system client is online.
	$online = wpcomsp_wayback_link_fixer_get_system_client()->is_online();
	// Set the transient
	$duration = apply_filters( 'wlf_archive_api_status_duration', \HOUR_IN_SECONDS );
	set_transient( 'wlf_archive_api_online', $online, $duration );
	return (bool) $online;
}
