<?php
/**
 * E2E clean up for seed-query-loop-links.php.
 *
 * Deletes the posts the seeder created, removes the link rows it inserted, and
 * restores every option it overwrote to the value it held before.
 *
 * Run via: wp eval-file e2e/fixtures/clean-query-loop-links.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/query-loop-shared.php';

global $wpdb;

// Remove the posts the seeder created, and their meta with them.
foreach ( (array) get_option( IAWMLF_E2E_QUERY_LOOP_POSTS, array() ) as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}
delete_option( IAWMLF_E2E_QUERY_LOOP_POSTS );

// Remove every link row the seeder inserted.
$links_table = Settings::get_link_table_name();
foreach ( iawmlf_e2e_query_loop_posts() as $urls ) {
	foreach ( $urls as $url ) {
		$wpdb->delete( $links_table, array( 'url' => $url ), array( '%s' ) );
	}
}

$backup = get_option( IAWMLF_E2E_QUERY_LOOP_BACKUP, array() );

foreach ( iawmlf_e2e_query_loop_options() as $option ) {
	// Absent before means absent after.
	if ( ! is_array( $backup ) || ! array_key_exists( $option, $backup ) || null === $backup[ $option ] ) {
		delete_option( $option );
		continue;
	}

	update_option( $option, $backup[ $option ] );
}

delete_option( IAWMLF_E2E_QUERY_LOOP_BACKUP );

echo "CLEANED=1\n";
