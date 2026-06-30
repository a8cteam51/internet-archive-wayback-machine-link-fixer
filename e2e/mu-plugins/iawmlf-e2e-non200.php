<?php
/**
 * E2E helper for: "a non-200 from the Internet Archive link checker must not
 * mark a link as broken". Mapped into wp-content/mu-plugins via .wp-env.json.
 *
 * Self-contained:
 *   - Forces the live web check endpoint (…/livewebcheck) to return whatever
 *     HTTP status the `iawmlf_e2e_force_status` option holds (0 = pass through).
 *   - `wp iawmlf-e2e-non200 seed <code>` seeds a post + a not-broken, never
 *     checked link, sets the forced status to <code> (default 502), and prints
 *     KEY=VALUE lines.
 *   - `wp iawmlf-e2e-non200 cleanup` removes that post + link and the forced status.
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\E2E
 */

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

const IAWMLF_E2E_LINK_URL  = 'https://example.com/iawmlf-e2e-non200';
const IAWMLF_E2E_POST_SLUG = 'iawmlf-e2e-non200';
const IAWMLF_E2E_OPTION    = 'iawmlf_e2e_force_status';

// Force the live web check endpoint to the configured status code.
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		$code = (int) get_option( IAWMLF_E2E_OPTION, 0 );

		if ( 0 === $code || ! is_string( $url ) || false === strpos( $url, 'livewebcheck' ) ) {
			return $pre;
		}

		return array(
			'headers'  => array(),
			// 200 needs a valid body; any non-200 makes the checker treat it as offline.
			'body'     => 200 === $code ? wp_json_encode( array( 'status' => 200 ) ) : '',
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
		);
	},
	10,
	3
);

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

/**
 * Delete the seeded post + link and clear the forced status (idempotent).
 */
function iawmlf_e2e_non200_cleanup() {
	global $wpdb;

	delete_option( IAWMLF_E2E_OPTION );
	$wpdb->delete( Settings::get_link_table_name(), array( 'url' => IAWMLF_E2E_LINK_URL ), array( '%s' ) );

	foreach ( get_posts(
		array(
			'name'        => IAWMLF_E2E_POST_SLUG,
			'post_type'   => 'post',
			'post_status' => 'any',
			'numberposts' => 1,
		)
	) as $p ) {
		wp_delete_post( $p->ID, true );
	}
}

WP_CLI::add_command(
	'iawmlf-e2e-non200 seed',
	function ( $args ) {
		global $wpdb;

		$code = isset( $args[0] ) ? (int) $args[0] : 502;

		// "replace_link" mode so the front-end enqueues and would rewrite broken links.
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );
		update_option( Settings::LINK_EXCLUSIONS, array() );

		iawmlf_e2e_non200_cleanup();
		update_option( IAWMLF_E2E_OPTION, $code );

		// Not broken and never checked, so the front-end performs a live check.
		$wpdb->insert(
			Settings::get_link_table_name(),
			array(
				'url'             => IAWMLF_E2E_LINK_URL,
				'archived'        => 'https://web.archive.org/web/2024/' . IAWMLF_E2E_LINK_URL,
				'checks'          => wp_json_encode( array() ),
				'message'         => '',
				'redirect_url'    => '',
				'is_broken'       => 0,
				'excluded'        => 0,
				'archive_process' => 'done',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);
		$link_id = (int) $wpdb->insert_id;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'IAWMLF E2E Non-200 Link Check',
				'post_name'    => IAWMLF_E2E_POST_SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_content' => sprintf( '<p>Check: <a href="%s">checked link</a></p>', esc_url( IAWMLF_E2E_LINK_URL ) ),
			)
		);

		update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link_id ) );

		echo 'POST_URL=' . get_permalink( $post_id ) . "\n";
		echo 'LINK_URL=' . IAWMLF_E2E_LINK_URL . "\n";
	}
);

WP_CLI::add_command(
	'iawmlf-e2e-non200 cleanup',
	function () {
		iawmlf_e2e_non200_cleanup();
		echo "CLEANED=1\n";
	}
);
