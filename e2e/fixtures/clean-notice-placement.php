<?php
/**
 * E2E clean up for seed-notice-placement.php.
 *
 * Restores every option the seeder overwrote to the value it held before, and
 * removes the notification cache transient in case the spec never consumed it.
 *
 * Run via: wp eval-file e2e/fixtures/clean-notice-placement.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded — is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/notice-placement-shared.php';

$admin = get_user_by( 'login', 'admin' );
if ( $admin ) {
	delete_transient( iawmlf_e2e_notice_placement_transient( (int) $admin->ID ) );
}

$backup = get_option( IAWMLF_E2E_NOTICE_PLACEMENT_BACKUP, array() );

foreach ( iawmlf_e2e_notice_placement_options() as $option ) {
	// Absent before means absent after.
	if ( ! is_array( $backup ) || ! array_key_exists( $option, $backup ) || null === $backup[ $option ] ) {
		delete_option( $option );
		continue;
	}

	update_option( $option, $backup[ $option ] );
}

delete_option( IAWMLF_E2E_NOTICE_PLACEMENT_BACKUP );

echo "CLEANED=1\n";
