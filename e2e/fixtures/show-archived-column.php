<?php
/**
 * E2E helper: save a posts list column preference for the admin that shows the
 * archived column.
 *
 * Saving any preference stops WordPress consulting the defaults, so the
 * plugin's default_hidden_columns filter no longer applies.
 *
 * Run via: wp eval-file e2e/fixtures/show-archived-column.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Table_Controller;

if ( ! class_exists( WP_Post_Table_Controller::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

global $wpdb;

$admin = get_user_by( 'login', 'admin' );
if ( ! $admin ) {
	fwrite( STDERR, "No 'admin' user found.\n" );
	exit( 1 );
}

// An empty list means nothing is hidden, the archived column included. Written
// the way core's Screen Options handler writes it, unprefixed via user meta.
delete_user_meta( (int) $admin->ID, $wpdb->get_blog_prefix() . 'manageedit-postcolumnshidden' );
update_user_meta( (int) $admin->ID, 'manageedit-postcolumnshidden', array() );

echo 'SHOWN=' . WP_Post_Table_Controller::ARCHIVED_COLUMN_KEY . "\n";
