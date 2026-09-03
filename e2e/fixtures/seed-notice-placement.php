<?php
/**
 * E2E seeder: admin notices must print inside the page's .wrap container.
 *
 * Sets up both notices the spec checks:
 *
 *   - Settings page: clears the Archive.org credentials so
 *     iawmlf_render_not_authenticated_notice() prints the unauthenticated notice.
 *   - Links page: writes a List_Table_Action_Notification_Cache transient for the
 *     admin user, so visiting the list with the matching query args replays it.
 *
 * Run via: wp eval-file e2e/fixtures/seed-notice-placement.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded — is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

$admin = get_user_by( 'login', 'admin' );
if ( ! $admin ) {
	fwrite( STDERR, "No 'admin' user found.\n" );
	exit( 1 );
}

// Settings page: no credentials means the unauthenticated notice renders.
delete_option( Settings::ARCHIVE_ORG_ACCESS_KEY );
delete_option( Settings::ARCHIVE_ORG_SECRET_KEY );

// Links page: replay a cached bulk-action notice. Key format mirrors
// List_Table_Action_Notification_Cache::compile_cache_key().
$action  = 'iawmlf_e2e_placement';
$key     = 'e2eplacement';
$message = 'IAWMLF e2e notice placement probe.';

set_transient(
	'iawmlf_list_table_action_cache_' . $admin->ID . '_' . $action . '_' . $key,
	array(
		array(
			'message' => $message,
			'type'    => 'error',
		),
	),
	5 * MINUTE_IN_SECONDS
);

// Output for the playwright spec. Each on its own line, KEY=VALUE.
echo "NOTICE_ACTION={$action}\n";
echo "NOTICE_KEY={$key}\n";
echo "NOTICE_MESSAGE={$message}\n";
