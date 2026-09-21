<?php
/**
 * E2E clean up for seed-capability-filter.php. (S049)
 *
 * Removes the editor account, the must-use plugin and the option the seeder set.
 *
 * Run via: wp eval-file e2e/fixtures/clean-capability-filter.php
 */

require_once __DIR__ . '/capability-filter-shared.php';

if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

$user = get_user_by( 'login', IAWMLF_E2E_CAP_USER );

if ( $user ) {
	wp_delete_user( (int) $user->ID );
}

if ( file_exists( IAWMLF_E2E_CAP_MU_FILE ) ) {
	wp_delete_file( IAWMLF_E2E_CAP_MU_FILE );
}

delete_option( IAWMLF_E2E_CAP_FILTER_OPTION );

echo "CLEANED=1\n";
