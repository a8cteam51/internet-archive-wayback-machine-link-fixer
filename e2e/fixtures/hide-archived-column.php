<?php
/**
 * E2E helper: clear the admin's saved column preference for the posts list.
 *
 * With no saved preference WordPress falls back to the defaults, which is where
 * the plugin's default_hidden_columns filter hides the archived column.
 *
 * Run via: wp eval-file e2e/fixtures/hide-archived-column.php
 */

global $wpdb;

$admin = get_user_by( 'login', 'admin' );
if ( ! $admin ) {
	fwrite( STDERR, "No 'admin' user found.\n" );
	exit( 1 );
}

// Core's Screen Options handler saves this unprefixed via update_user_meta, but
// get_user_option() reads the blog-prefixed key first. Clear both.
delete_user_meta( (int) $admin->ID, 'manageedit-postcolumnshidden' );
delete_user_meta( (int) $admin->ID, $wpdb->get_blog_prefix() . 'manageedit-postcolumnshidden' );

echo "HIDDEN=1\n";
