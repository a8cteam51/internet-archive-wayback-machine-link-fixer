<?php
/**
 * E2E helper for the Fixer Option modes. Mapped into wp-content/mu-plugins via .wp-env.json.
 *
 * Seeds one broken link that already has an archived version and a recent check, so the
 * front-end checker resolves it locally (no live API call). A spec can then toggle the
 * fixer mode and assert the front-end behaviour for each:
 *
 *   - replace_link : href rewritten to the archive URL + `iawmlf-broken-link` class added.
 *   - check_only   : link checked (data-iawmlf-archived-url set) but href left untouched, no class.
 *   - do_nothing   : the checker script is never enqueued, so the link is left entirely alone.
 *
 * Commands:
 *   wp iawmlf-e2e-fixer-mode seed         -> seeds the post + broken/archived link, prints URLs.
 *   wp iawmlf-e2e-fixer-mode mode <value> -> sets the fixer option (replace_link|check_only|do_nothing).
 *   wp iawmlf-e2e-fixer-mode cleanup      -> removes the post + link and resets the fixer option.
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\E2E
 */

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

const IAWMLF_E2E_FIXER_LINK_URL     = 'https://example.com/iawmlf-e2e-fixer-mode';
const IAWMLF_E2E_FIXER_ARCHIVED_URL = 'https://web.archive.org/web/2024/https://example.com/iawmlf-e2e-fixer-mode';
const IAWMLF_E2E_FIXER_POST_SLUG    = 'iawmlf-e2e-fixer-mode';

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

/**
 * Delete the seeded post + link and reset the fixer option (idempotent).
 *
 * @return void
 */
function iawmlf_e2e_fixer_mode_cleanup(): void {
	global $wpdb;

	update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );
	$wpdb->delete( Settings::get_link_table_name(), array( 'url' => IAWMLF_E2E_FIXER_LINK_URL ), array( '%s' ) );

	foreach ( get_posts(
		array(
			'name'        => IAWMLF_E2E_FIXER_POST_SLUG,
			'post_type'   => 'post',
			'post_status' => 'any',
			'numberposts' => 1,
		)
	) as $p ) {
		wp_delete_post( $p->ID, true );
	}
}

// Seed one broken link with an archived version + a recent check, and a post that links to it.
WP_CLI::add_command(
	'iawmlf-e2e-fixer-mode seed',
	function (): void {
		global $wpdb;

		update_option( Settings::POST_ACTIVATION_ONBOARDING_KEY, Settings::ONBOARDING_COMPLETED_OPTION );
		update_option( Settings::SETUP_WIZARD_COMPLETED_KEY, true );
		update_option( Settings::SETUP_WIZARD_STEP_KEY, 'complete' );
		update_option( Settings::LINK_EXCLUSIONS, array() );

		iawmlf_e2e_fixer_mode_cleanup();

		// Broken, with an archived version and a recent check so the front-end resolves it
		// locally (checks within the delay window skip the live REST call).
		$wpdb->insert(
			Settings::get_link_table_name(),
			array(
				'url'             => IAWMLF_E2E_FIXER_LINK_URL,
				'archived'        => IAWMLF_E2E_FIXER_ARCHIVED_URL,
				'checks'          => wp_json_encode( array( array( 'date' => gmdate( 'Y-m-d H:i:s' ), 'http_code' => 404 ) ) ),
				'message'         => '',
				'redirect_url'    => '',
				'is_broken'       => 1,
				'excluded'        => 0,
				'archive_process' => 'done',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);
		$link_id = (int) $wpdb->insert_id;

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'IAWMLF E2E Fixer Mode',
				'post_name'    => IAWMLF_E2E_FIXER_POST_SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_content' => sprintf( '<p>Check: <a href="%s">fixer mode link</a></p>', esc_url( IAWMLF_E2E_FIXER_LINK_URL ) ),
			)
		);

		update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link_id ) );

		echo 'POST_URL=' . get_permalink( $post_id ) . "\n";
		echo 'LINK_URL=' . IAWMLF_E2E_FIXER_LINK_URL . "\n";
		echo 'ARCHIVED_URL=' . IAWMLF_E2E_FIXER_ARCHIVED_URL . "\n";
	}
);

// Set the fixer option: wp iawmlf-e2e-fixer-mode mode <replace_link|check_only|do_nothing>.
WP_CLI::add_command(
	'iawmlf-e2e-fixer-mode mode',
	function ( array $args ): void {
		$mode  = isset( $args[0] ) ? (string) $args[0] : '';
		$valid = array(
			Settings::FIXER_OPTION_REPLACE_LINK,
			Settings::FIXER_OPTION_CHECK_ONLY,
			Settings::FIXER_OPTION_DO_NOTHING,
		);

		if ( ! in_array( $mode, $valid, true ) ) {
			WP_CLI::error( 'Invalid mode: ' . $mode );
		}

		update_option( Settings::FIXER_OPTION, $mode );
		echo 'MODE=' . $mode . "\n";
	}
);

WP_CLI::add_command(
	'iawmlf-e2e-fixer-mode cleanup',
	function (): void {
		iawmlf_e2e_fixer_mode_cleanup();
		echo "CLEANED=1\n";
	}
);
