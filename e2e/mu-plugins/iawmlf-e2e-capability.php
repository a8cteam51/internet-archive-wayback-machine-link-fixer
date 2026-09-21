<?php
/**
 * E2E helper for the reporting capability filter. Mapped into wp-content/mu-plugins via .wp-env.json.
 *
 * Adds iawmlf_reporting_page_capability on init, which is where a site would
 * normally add it, and crucially AFTER the plugin has already initialised on
 * plugins_loaded. Reading the capability at registration time ignored it.
 *
 * The filter is gated on an option so a single run can assert both directions:
 * with it off an editor must see nothing, with it on they must see the dashboard,
 * the widget, the post column and the links report, but never Advanced Settings.
 *
 * Commands:
 *   wp iawmlf-e2e-capability seed         -> creates the editor account, prints credentials.
 *   wp iawmlf-e2e-capability filter <0|1> -> turns the init-time filter on or off.
 *   wp iawmlf-e2e-capability cleanup      -> removes the editor account and the option.
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\E2E
 */

defined( 'ABSPATH' ) || exit;

const IAWMLF_E2E_CAP_USER   = 'iawmlf_e2e_cap_editor';
const IAWMLF_E2E_CAP_PASS   = 'iawmlf-e2e-cap-pass';
const IAWMLF_E2E_CAP_OPTION = 'iawmlf_e2e_cap_filter_enabled';

// Registered on every request, web included. That is the point of the test.
add_action(
	'init',
	function (): void {
		if ( '1' !== (string) get_option( IAWMLF_E2E_CAP_OPTION, '' ) ) {
			return;
		}

		add_filter(
			'iawmlf_reporting_page_capability',
			function (): string {
				return 'edit_posts';
			}
		);
	}
);

if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

// Create the editor, reusing the account if a previous run left it behind.
WP_CLI::add_command(
	'iawmlf-e2e-capability seed',
	function (): void {
		$user = get_user_by( 'login', IAWMLF_E2E_CAP_USER );

		if ( $user ) {
			$user_id = (int) $user->ID;
			wp_set_password( IAWMLF_E2E_CAP_PASS, $user_id );
		} else {
			$user_id = wp_insert_user(
				array(
					'user_login' => IAWMLF_E2E_CAP_USER,
					'user_pass'  => IAWMLF_E2E_CAP_PASS,
					'user_email' => IAWMLF_E2E_CAP_USER . '@example.com',
					'role'       => 'editor',
				)
			);

			if ( is_wp_error( $user_id ) ) {
				WP_CLI::error( 'Could not create the editor: ' . $user_id->get_error_message() );
			}
		}

		// Start with the filter off, so the spec's first assertion is the control.
		update_option( IAWMLF_E2E_CAP_OPTION, '0' );

		echo 'EDITOR_USER=' . IAWMLF_E2E_CAP_USER . "\n";
		echo 'EDITOR_PASS=' . IAWMLF_E2E_CAP_PASS . "\n";
		echo 'EDITOR_ID=' . (int) $user_id . "\n";
		echo 'ADMIN_URL=' . admin_url() . "\n";
	}
);

// Turn the init-time filter on or off: wp iawmlf-e2e-capability filter <0|1>.
WP_CLI::add_command(
	'iawmlf-e2e-capability filter',
	function ( array $args ): void {
		$enabled = isset( $args[0] ) ? (string) $args[0] : '';

		if ( ! in_array( $enabled, array( '0', '1' ), true ) ) {
			WP_CLI::error( 'Expected 0 or 1, got: ' . $enabled );
		}

		update_option( IAWMLF_E2E_CAP_OPTION, $enabled );
		echo 'FILTER=' . $enabled . "\n";
	}
);

WP_CLI::add_command(
	'iawmlf-e2e-capability cleanup',
	function (): void {
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$user = get_user_by( 'login', IAWMLF_E2E_CAP_USER );

		if ( $user ) {
			wp_delete_user( (int) $user->ID );
		}

		delete_option( IAWMLF_E2E_CAP_OPTION );

		echo "CLEANED=1\n";
	}
);
