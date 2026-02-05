<?php

/**
 * Plugin Functions.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Plugin;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Migrations;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Snapshot_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Link_Checker_Client;

// region

/**
 * Polyfill for mb_strimwidth.
 */
if ( ! function_exists( 'mb_strimwidth' ) ) {
	/**
	 * Polyfill for mb_strimwidth.
	 *
	 * @param string  $source      The string to trim.
	 * @param integer $start       The starting position.
	 * @param integer $width       The width to trim to.
	 * @param string  $trim_marker The marker to append if the string is trimmed.
	 * @param string  $encoding    The character encoding.
	 *
	 * @return string
	 */
	function mb_strimwidth( $source, $start, $width, $trim_marker = '', $encoding = 'UTF-8' ) {
		// Fallback using mb_substr if available
		if ( function_exists( 'mb_substr' ) ) {
			$substr = mb_substr( $source, $start, $width, $encoding );
			if ( mb_strlen( $source, $encoding ) > $width ) {
				return $substr . $trim_marker;
			}
			return $substr;
		} else {
			// Rough fallback using substr (not multibyte-safe!)
			$substr = substr( $source, $start, $width );
			if ( strlen( $source ) > $width ) {
				return $substr . $trim_marker;
			}
			return $substr;
		}
	}
}

/**
 * Polyfill for mb_strlen.
 */
if ( ! function_exists( 'mb_strlen' ) ) {
	/**
	 * Polyfill for mb_strlen.
	 *
	 * @param string $source   The string to measure.
	 * @param string $encoding The character encoding.
	 *
	 * @return integer
	 */
	function mb_strlen( $source, $encoding = 'UTF-8' ) {
		// Use preg_match_all to count UTF-8 characters
		if ( 'UTF-8' === $encoding ) {
			return preg_match_all( '/./u', $source, $matches );
		}

		// Fallback: use strlen (not multibyte-safe!)
		return strlen( $source );
	}
}

/**
 * Returns the plugin's main class instance.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  Plugin
 */
function iawmlf_get_plugin_instance(): Plugin {
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
function iawmlf_activate(): void {

	// Run migrations.
	Migrations::up();

	// If already marked as completed, do nothing.
	if ( Settings::ONBOARDING_COMPLETED_OPTION === Settings::get_onboarding_status( Settings::ONBOARDING_PENDING_OPTION )
	|| Settings::is_wizard_completed() ) {
		return;
	}

	// Set the onboarding status to pending.
	Settings::set_onboarding_status( Settings::ONBOARDING_PENDING_OPTION );
}

/**
 * Uninstall hook.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  void
 */
function iawmlf_uninstall(): void {
	// If we are not dropping tables on deactivation, do nothing.
	if ( ! Settings::drop_tables_on_uninstall() ) {
		return;
	}

	Migrations::down();
	Settings::clear_all_options();
	WP_Post_Controller::clear_all_post_meta();
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
function iawmlf_escape_http_status_code( $code ): ?int {
	$code = absint( (int) $code );
	return $code > 0 ? $code : null;
}

/**
 * Gets the current Snapshot Client.
 *
 * @since 1.2.0
 *
 * @return Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client
 */
function iawmlf_get_snapshot_client(): Snapshot_Client {
	return apply_filters( 'iawmlf_snapshot_client', new HTTP_Snapshot_Client() );
}

/**
 * Gets the current Link Checker Client.
 *
 * @since 1.2.0
 *
 * @return Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client
 */
function iawmlf_get_link_checker_client(): Link_Checker_Client {
	return apply_filters( 'iawmlf_link_checker_client', new HTTP_Link_Checker_Client() );
}

/**
 * Get the current System Client.
 *
 * @since 1.3.0
 *
 * @return Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\System_Client
 */
function iawmlf_get_system_client(): \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\System_Client {
	return apply_filters( 'iawmlf_system_client', new \Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_System_Client() );
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
function iawmlf_render_template( string $template, array $args = array(), bool $render = true ) {

	$path = IAWMLF_PATH . 'templates/' . $template;

	// Throw an error if the template does not exist.
	if ( ! file_exists( $path ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: template path */
				esc_html__( 'The template %s does not exist.', 'internet-archive-wayback-machine-link-fixer' ),
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
 * Get image asset URL from filename.
 *
 * @since 1.2.0
 *
 * @param string $filename The filename.
 *
 * @return string
 */
function iawmlf_get_image_asset_url( string $filename ): string {
	return esc_url( IAWMLF_URL . 'assets/images/' . $filename );
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
function iawmlf_trim_string( string $text, int $length, string $suffix = '...' ): string {
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
function iawmlf_get_date_format(): string {
	return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
}

/**
 * Renders the notice about not authenticated with the Wayback Machine.
 *
 * @since 1.3.0
 *
 * @return void
 */
function iawmlf_render_not_authenticated_notice(): void {
	$in_unauthenticated_mode = __( 'You are using Link Fixer in unauthenticated mode, which restricts you to 4000 new snapshots per day. To unlock higher limits, please enter your API credentials to authenticate with Archive.org.', 'internet-archive-wayback-machine-link-fixer' );

	// If the archive api is not configured.
	if ( ! Settings::is_archive_api_configured() ) {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php echo esc_html( $in_unauthenticated_mode ); ?>
			</p>
		</div>
		<?php
		return;
	}

	// If the creds are invalid.
	if ( ! Settings::has_valid_archive_api_credentials() ) {
		$message  = __( 'Your Archive.org API credentials are invalid. Please check your settings.', 'internet-archive-wayback-machine-link-fixer' );
		$message .= ' ' . $in_unauthenticated_mode;
		?>
		<div class="notice notice-error">
			<p>
				<?php
				print wp_kses_post(
					sprintf(
						// translators: %s is a link to the settings page.
						__( 'Your Archive.org API credentials are invalid. <a href="%s">Please check your settings.</a>. As a result you are in in unauthenticated mode, which restricts you to 4000 new snapshots per day.', 'internet-archive-wayback-machine-link-fixer' ),
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
function iawmlf_render_wayback_offline_notice(): void {
	// If the Wayback Machine is online, bail.
	if ( Settings::is_archive_api_online() ) {
		return;
	}

	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'The Wayback Machine is currently offline. Please try again later.', 'internet-archive-wayback-machine-link-fixer' ); ?>
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
function iawmlf_is_archive_link( string $url ): bool {
	$urls = array(
		'https://web.archive.org/web/',
		'http://web.archive.org/web/',
		'https://web-wp.archive.org/web/',
		'http://web-wp.archive.org/web/',
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
function iawmlf_is_current_site_link( string $url ): bool {
	// Get the site urls with all protocols.
	$site_urls = array(
		get_site_url( null, '', 'https' ),
		get_site_url( null, '', 'http' ),
	);
	// Normalize the URL.
	$normalized_url = iawmlf_normalize_url( $url );

	// Noprmalize the site URLs.
	$site_urls = array_map( 'iawmlf_normalize_url', $site_urls );

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
function iawmlf_normalize_url( string $url ): string {
	$url = rtrim( $url, '/' );

	// URL Encode the url parameters.
	$url_parts = wp_parse_url( $url );

	// If we have a path, encode it.
	if ( isset( $url_parts['path'] ) ) {
		// Decode first to avoid double-encoding, then re-encode
		$decoded_path      = urldecode( $url_parts['path'] );
		$path_parts        = explode( '/', $decoded_path );
		$path_parts        = array_map( 'rawurlencode', $path_parts );
		$url_parts['path'] = implode( '/', $path_parts );
	}

	// If we have a query, encode it.
	if ( isset( $url_parts['query'] ) ) {
		// Split query string into individual parameters
		$query_pairs   = explode( '&', $url_parts['query'] );
		$encoded_pairs = array();

		foreach ( $query_pairs as $pair ) {
			if ( strpos( $pair, '=' ) !== false ) {
				// Has a value: key=value
				list( $key, $value ) = explode( '=', $pair, 2 );
				$encoded_pairs[]     = rawurlencode( urldecode( $key ) ) . '=' . rawurlencode( urldecode( $value ) );
			} else {
				// No value: just key
				$encoded_pairs[] = rawurlencode( urldecode( $pair ) );
			}
		}

		$url_parts['query'] = implode( '&', $encoded_pairs );
	}

	// If we have a fragment, encode it.
	if ( isset( $url_parts['fragment'] ) ) {
		// Decode first to avoid double-encoding, then re-encode
		$decoded_fragment      = urldecode( $url_parts['fragment'] );
		$url_parts['fragment'] = str_replace( '%21', '!', rawurlencode( $decoded_fragment ) );
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
function iawmlf_get_admin_post_type_link( string $post_type, string $target = '_self' ): ?string {
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
function iawmlf_is_archive_api_online( bool $force = false ): bool {
	// Try to get from transient.
	$online = get_transient( 'iawmlf_archive_api_online' );
	if ( false !== (bool) $online && false === $force ) {
		return (bool) $online;
	}

	// Check if the system client is online.
	$online = iawmlf_get_system_client()->is_online();
	// Set the transient
	$duration = apply_filters( 'iawmlf_archive_api_status_duration', \HOUR_IN_SECONDS );
	set_transient( 'iawmlf_archive_api_online', $online, $duration );
	return (bool) $online;
}

/**
 * Converts a Internet Archive status code to a human readable message.
 *
 * @since 1.4.0
 *
 * @param string $status_code The status code to convert.
 *
 * @return string
 */
function iawmlf_get_human_readable_status_message( string $status_code ): string {

	// If the error message doesnt start with error:, return the original message.
	if ( 0 !== strpos( $status_code, 'error:' ) ) {
		return $status_code;
	}

	$status_code = trim( $status_code );

	$messages = array(
		'error:bad-gateway'                     => 'Bad Gateway for URL (HTTP status=502).',
		'error:bad-request'                     => 'The server could not understand the request due to invalid syntax. (HTTP status=401)',
		'error:bandwidth-limit-exceeded'        => 'The target server has exceeded the bandwidth specified by the server administrator. (HTTP status=509).',
		'error:blocked'                         => 'The target site is blocking us (HTTP status=999).',
		'error:blocked-client-ip'               => 'Anonymous clients which are listed in https://www.spamhaus.org/xbl/ or https://www.spamhaus.org/sbl/ lists (spams & exploits) are blocked. Tor exit nodes are excluded from this filter.',
		'error:blocked-url'                     => 'We use a URL block list based on Mozilla web tracker lists to avoid unwanted captures.',
		'error:browsing-timeout'                => 'SPN2 back-end headless browser timeout.',
		'error:capture-location-error'          => 'SPN2 back-end cannot find the created capture location. (system error).',
		'error:cannot-fetch'                    => 'Cannot fetch the target URL due to system overload.',
		'error:celery'                          => 'Cannot start capture task.',
		'error:filesize-limit'                  => 'Cannot capture web resources over 2GB.',
		'error:ftp-access-denied'               => 'Tried to capture an FTP resource but access was denied.',
		'error:gateway-timeout'                 => 'The target server didn\'t respond in time. (HTTP status=504).',
		'error:http-version-not-supported'      => 'The target server does not support the HTTP protocol version used in the request for URL (HTTP status=505).',
		'error:internal-server-error'           => 'SPN internal server error.',
		'error:invalid-url-syntax'              => 'Target URL syntax is not valid.',
		'error:invalid-server-response'         => 'The target server response was invalid. (e.g. invalid headers, invalid content encoding, etc).',
		'error:invalid-host-resolution'         => 'Couldn\'t resolve the target host.',
		'error:job-failed'                      => 'Capture failed due to system error.',
		'error:method-not-allowed'              => 'The request method is known by the server but has been disabled and cannot be used (HTTP status=405).',
		'error:not-implemented'                 => 'The request method is not supported by the server and cannot be handled for URL (HTTP status=501).',
		'error:no-browsers-available'           => 'SPN2 back-end headless browser cannot run.',
		'error:network-authentication-required' => 'The client needs to authenticate to gain network access to the URL (HTTP status=511).',
		'error:no-access'                       => 'Target URL could not be accessed (status=403).',
		'error:not-found'                       => 'Target URL not found (status=404).',
		'error:proxy-error'                     => 'SPN2 back-end proxy error.',
		'error:protocol-error'                  => 'HTTP connection broken. (A possible cause of this error is "IncompleteRead").',
		'error:read-timeout'                    => 'HTTP connection read timeout.',
		'error:soft-time-limit-exceeded'        => 'Capture duration exceeded 45s time limit and was terminated.',
		'error:service-unavailable'             => 'Service unavailable for URL (HTTP status=503).',
		'error:too-many-daily-captures'         => 'This URL has been captured 10 times today. We cannot make any more captures.',
		'error:too-many-redirects'              => 'Too many redirects. SPN2 tries to follow 3 redirects automatically.',
		'error:too-many-requests'               => 'The target host has received too many requests from SPN and it is blocking it. (HTTP status=429). Note that captures to the same host will be delayed for 10-20s after receiving this response to remedy the situation.',
		'error:user-session-limit'              => 'User has reached the limit of concurrent active capture sessions.',
		'error:unauthorized'                    => 'The server requires authentication (HTTP status=401).',
		'error:max-daily-bandwidth'             => 'An authenticated user can archive up to 5GB per day.',
		'error:max-daily-bandwidth-from-ip'     => 'An anonymous user can archive up to 2GB per day.',
		'error:max-daily-bandwidth-host'        => 'SPN2 can archive up to 100GB per day from a host.',
	);

	$message = $messages[ $status_code ] ?? ( 'Uknown: ' . $status_code );

	return (string) apply_filters( 'iawmlf_human_readable_status_message', $message, $status_code );
}

/**
 * Checks if a given status code should make the link excluded.
 *
 * @since 1.4.0
 *
 * @param string $status_code The status code to check.
 *
 * @return boolean
 */
function iawmlf_is_excluded_status_code( string $status_code ): bool {
	// If status code doesnt start with error, return true.
	if ( 0 !== strpos( $status_code, 'error' ) ) {
		return false;
	}

	$allowed_status_codes = array(
		'error:browsing-timeout',
		'error:capture-location-error',
		'error:internal-server-error',
		'error:job-failed',
		'error:proxy-error',
		'error:too-many-daily-captures',
		'error:too-many-requests',
		'error:max-daily-bandwidth',
		'error:max-daily-bandwidth-from-ip',
		'error:max-daily-bandwidth-host',
	);

	$allowed_status_codes = apply_filters( 'iawmlf_excluded_status_codes', $allowed_status_codes );

	return ! in_array( $status_code, $allowed_status_codes, true );
}
