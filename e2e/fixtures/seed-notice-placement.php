<?php
/**
 * E2E seeder: admin notices must print inside the page's .wrap container.
 *
 * Sets up both notices the spec checks, and does not depend on any other spec
 * having run first:
 *
 *   - Onboarding is marked complete, or every admin page redirects to the wizard.
 *   - Settings page: clears the Archive.org credentials so
 *     iawmlf_render_not_authenticated_notice() prints the unauthenticated notice.
 *   - Links page: writes a List_Table_Action_Notification_Cache transient for the
 *     admin user, so visiting the list with the matching query args replays it.
 *
 * Anything overwritten is stashed so clean-notice-placement.php can restore it.
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

require_once __DIR__ . '/notice-placement-shared.php';

// Stash the options this seeder overwrites, so the state can be put back.
$backup = array();
foreach ( iawmlf_e2e_notice_placement_options() as $option ) {
	$backup[ $option ] = get_option( $option, null );
}
update_option( IAWMLF_E2E_NOTICE_PLACEMENT_BACKUP, $backup );

// Onboarding must be complete or every admin page redirects to the setup wizard.
update_option( Settings::POST_ACTIVATION_ONBOARDING_KEY, Settings::ONBOARDING_COMPLETED_OPTION );
update_option( Settings::SETUP_WIZARD_COMPLETED_KEY, true );
update_option( Settings::SETUP_WIZARD_STEP_KEY, 'complete' );

// Settings page: no credentials means the unauthenticated notice renders.
delete_option( Settings::ARCHIVE_ORG_ACCESS_KEY );
delete_option( Settings::ARCHIVE_ORG_SECRET_KEY );

// Links page: replay a cached bulk-action notice. Key format mirrors
// List_Table_Action_Notification_Cache::compile_cache_key().
set_transient(
	iawmlf_e2e_notice_placement_transient( (int) $admin->ID ),
	array(
		array(
			'message' => IAWMLF_E2E_NOTICE_PLACEMENT_MESSAGE,
			'type'    => 'error',
		),
	),
	5 * MINUTE_IN_SECONDS
);

// Output for the playwright spec. Each on its own line, KEY=VALUE.
echo 'NOTICE_ACTION=' . IAWMLF_E2E_NOTICE_PLACEMENT_ACTION . "\n";
echo 'NOTICE_KEY=' . IAWMLF_E2E_NOTICE_PLACEMENT_KEY . "\n";
echo 'NOTICE_MESSAGE=' . IAWMLF_E2E_NOTICE_PLACEMENT_MESSAGE . "\n";
