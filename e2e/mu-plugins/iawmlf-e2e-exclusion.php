<?php
/**
 * E2E helper for the link-details exclusion "unwind" test. Mapped into wp-content/mu-plugins
 * via .wp-env.json.
 *
 * Lets a spec drive all three exclusion layers on one seeded link and peel them back one at a
 * time to verify the panel's priority order (per-link flag -> settings list -> built-in list):
 *
 *   - built-in layer : an option-gated `iawmlf_bundled_link_exclusions` filter (the real list is
 *                      a code constant, so this is the only runtime way to toggle it).
 *   - settings layer : the spec flips the normal `iawmlf_link_exclusions` option directly.
 *   - per-link flag  : `wp iawmlf-e2e-exclusion flag <link_id> <0|1>`.
 *
 * Commands:
 *   wp iawmlf-e2e-exclusion seed     -> seeds one broken, un-excluded link, prints LINK_ID.
 *   wp iawmlf-e2e-exclusion flag …   -> sets that link's per-link excluded flag.
 *   wp iawmlf-e2e-exclusion cleanup  -> removes the link + all e2e exclusion state.
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\E2E
 */

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;

const IAWMLF_E2E_EXCL_URL    = 'https://example.com/iawmlf-e2e-panel-link';
const IAWMLF_E2E_BUILTIN_OPT = 'iawmlf_e2e_builtin_exclusion';

// Built-in list layer, toggled by an option so the spec can flip it via wp-cli.
add_filter(
	'iawmlf_bundled_link_exclusions',
	function ( array $list ): array {
		if ( get_option( IAWMLF_E2E_BUILTIN_OPT ) ) {
			$list[] = '*iawmlf-e2e-panel-link*';
		}
		return $list;
	}
);

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

/**
 * Remove the seeded link and all e2e exclusion state (idempotent).
 *
 * @return void
 */
function iawmlf_e2e_exclusion_cleanup(): void {
	global $wpdb;
	delete_option( IAWMLF_E2E_BUILTIN_OPT );
	update_option( Settings::LINK_EXCLUSIONS, array() );
	$wpdb->delete( Settings::get_link_table_name(), array( 'url' => IAWMLF_E2E_EXCL_URL ), array( '%s' ) );
}

// Seed one broken, un-excluded link and mark onboarding complete so admin pages render.
WP_CLI::add_command(
	'iawmlf-e2e-exclusion seed',
	function (): void {
		global $wpdb;

		update_option( Settings::POST_ACTIVATION_ONBOARDING_KEY, Settings::ONBOARDING_COMPLETED_OPTION );
		update_option( Settings::SETUP_WIZARD_COMPLETED_KEY, true );
		update_option( Settings::SETUP_WIZARD_STEP_KEY, 'complete' );

		iawmlf_e2e_exclusion_cleanup();

		$wpdb->insert(
			Settings::get_link_table_name(),
			array(
				'url'             => IAWMLF_E2E_EXCL_URL,
				'archived'        => 'https://web.archive.org/web/2024/' . IAWMLF_E2E_EXCL_URL,
				'checks'          => wp_json_encode( array( array( 'date' => gmdate( 'Y-m-d H:i:s' ), 'http_code' => 404 ) ) ),
				'message'         => '',
				'redirect_url'    => '',
				'is_broken'       => 1,
				'excluded'        => 0,
				'archive_process' => 'done',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		echo 'LINK_ID=' . (int) $wpdb->insert_id . "\n";
	}
);

// Toggle the per-link excluded flag: wp iawmlf-e2e-exclusion flag <link_id> <0|1>.
WP_CLI::add_command(
	'iawmlf-e2e-exclusion flag',
	function ( array $args ): void {
		$id = isset( $args[0] ) ? (int) $args[0] : 0;
		$on = isset( $args[1] ) && '0' !== $args[1];

		$repo = new Link_Repository();
		$link = $repo->find_by_id( $id );
		if ( $link ) {
			$link->set_excluded( $on );
			$repo->upsert( $link );
		}

		echo 'FLAG=' . ( $on ? '1' : '0' ) . "\n";
	}
);

WP_CLI::add_command(
	'iawmlf-e2e-exclusion cleanup',
	function (): void {
		iawmlf_e2e_exclusion_cleanup();
		echo "CLEANED=1\n";
	}
);
