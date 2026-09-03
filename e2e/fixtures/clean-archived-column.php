<?php
/**
 * E2E clean up for seed-archived-column.php.
 *
 * Deletes the posts the seeder created, restores every option it overwrote to
 * the value it held before, and clears the admin's saved column preference for
 * the posts list so the next run starts from the defaults again.
 *
 * Run via: wp eval-file e2e/fixtures/clean-archived-column.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/archived-column-shared.php';

// Remove the posts the seeder created.
foreach ( (array) get_option( IAWMLF_E2E_ARCHIVED_COLUMN_POSTS, array() ) as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}
delete_option( IAWMLF_E2E_ARCHIVED_COLUMN_POSTS );

// The spec ticks the Screen Options box, which saves a per-user preference.
// Core writes it unprefixed via update_user_meta, get_user_option() reads the
// blog-prefixed key first, so both have to go.
global $wpdb;

$admin = get_user_by( 'login', 'admin' );
if ( $admin ) {
	delete_user_meta( (int) $admin->ID, 'manageedit-postcolumnshidden' );
	delete_user_meta( (int) $admin->ID, $wpdb->get_blog_prefix() . 'manageedit-postcolumnshidden' );
}

$backup = get_option( IAWMLF_E2E_ARCHIVED_COLUMN_BACKUP, array() );

foreach ( iawmlf_e2e_archived_column_options() as $option ) {
	// Absent before means absent after.
	if ( ! is_array( $backup ) || ! array_key_exists( $option, $backup ) || null === $backup[ $option ] ) {
		delete_option( $option );
		continue;
	}

	update_option( $option, $backup[ $option ] );
}

delete_option( IAWMLF_E2E_ARCHIVED_COLUMN_BACKUP );

echo "CLEANED=1\n";
