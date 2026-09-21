<?php
/**
 * E2E seeder: the reporting capability filter must be honoured when added on init. (S049)
 *
 * Dashboard_Page, Dashboard_Notifications and WP_Post_Table_Controller are all
 * initialized on plugins_loaded. A site widening access with the documented
 * iawmlf_reporting_page_capability filter does so on init, which is later, so
 * reading the capability at registration time silently ignores it.
 *
 * This seeds an editor account plus a must-use plugin that adds exactly that
 * filter on init, gated behind an option so the spec can prove both directions
 * in one run.
 *
 * Run via: wp eval-file e2e/fixtures/seed-capability-filter.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/capability-filter-shared.php';

// The editor account. Reuse it if a previous run left it behind.
$user = get_user_by( 'login', IAWMLF_E2E_CAP_USER );

if ( ! $user ) {
	$user_id = wp_insert_user(
		array(
			'user_login' => IAWMLF_E2E_CAP_USER,
			'user_pass'  => IAWMLF_E2E_CAP_PASS,
			'user_email' => IAWMLF_E2E_CAP_USER . '@example.com',
			'role'       => 'editor',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		fwrite( STDERR, 'Could not create the editor: ' . $user_id->get_error_message() . "\n" );
		exit( 1 );
	}
} else {
	$user_id = $user->ID;
	wp_set_password( IAWMLF_E2E_CAP_PASS, (int) $user_id );
}

// The must-use plugin. Loading early is the point: it registers on init, which
// still runs after the plugin has already decided on plugins_loaded.
if ( ! is_dir( WPMU_PLUGIN_DIR ) && ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
	fwrite( STDERR, 'Could not create ' . WPMU_PLUGIN_DIR . "\n" );
	exit( 1 );
}

$mu_plugin = <<<'PHP'
<?php
/**
 * Plugin Name: IAWMLF E2E capability filter
 *
 * Widens the reporting capability on init, the normal place a site would do it.
 * Only active while iawmlf_e2e_cap_filter_enabled is set, so one e2e run can
 * assert both the filtered and unfiltered behaviour.
 */

add_action(
	'init',
	function () {
		if ( '1' !== (string) get_option( 'iawmlf_e2e_cap_filter_enabled', '' ) ) {
			return;
		}

		add_filter(
			'iawmlf_reporting_page_capability',
			function () {
				return 'edit_posts';
			}
		);
	}
);
PHP;

if ( false === file_put_contents( IAWMLF_E2E_CAP_MU_FILE, $mu_plugin ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	fwrite( STDERR, 'Could not write ' . IAWMLF_E2E_CAP_MU_FILE . "\n" );
	exit( 1 );
}

// Start with the filter off, so the spec's first assertion is the control.
update_option( IAWMLF_E2E_CAP_FILTER_OPTION, '0' );

echo 'EDITOR_USER=' . IAWMLF_E2E_CAP_USER . "\n";
echo 'EDITOR_PASS=' . IAWMLF_E2E_CAP_PASS . "\n";
echo 'EDITOR_ID=' . (int) $user_id . "\n";
echo 'ADMIN_URL=' . admin_url() . "\n";
echo "SEEDED=1\n";
