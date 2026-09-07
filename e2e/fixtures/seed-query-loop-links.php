<?php
/**
 * E2E seeder: link data on a query loop page.
 *
 * The front-end script only receives the current post's links through
 * wp_localize_script, so every other post shown in a loop depends entirely on
 * its own payload span. This seeds three posts with two distinct links each,
 * so the spec can prove every post in the loop still emits a span carrying all
 * of that post's links and none of any other post's.
 *
 * Run via: wp eval-file e2e/fixtures/seed-query-loop-links.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/query-loop-shared.php';

global $wpdb;

$links_table  = Settings::get_link_table_name();
$archived_pre = 'https://web.archive.org/web/2024/';
$recent_check = gmdate( 'Y-m-d H:i:s' );

// Back up every option we are about to overwrite.
$backup = array();
foreach ( iawmlf_e2e_query_loop_options() as $option ) {
	$backup[ $option ] = get_option( $option, null );
}
update_option( IAWMLF_E2E_QUERY_LOOP_BACKUP, $backup );

// The span is only rendered when the fixer is doing something with links.
update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

// A matching exclusion pattern would send us down the wrong code path.
update_option( Settings::LINK_EXCLUSIONS, array() );

$post_ids = array();

foreach ( iawmlf_e2e_query_loop_posts() as $label => $urls ) {
	$link_ids = array();
	$anchors  = array();

	foreach ( $urls as $index => $url ) {
		// Clear any row left by a previous run before inserting a fresh one.
		$wpdb->delete( $links_table, array( 'url' => $url ), array( '%s' ) );

		$inserted = $wpdb->insert(
			$links_table,
			array(
				'url'             => $url,
				'archived'        => $archived_pre . $url,
				'checks'          => wp_json_encode( array( array( 'date' => $recent_check, 'http_code' => 404 ) ) ),
				'message'         => '',
				'redirect_url'    => '',
				'is_broken'       => 1,
				'excluded'        => 0,
				'archive_process' => 'done',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			fwrite( STDERR, "Insert failed for {$url}: {$wpdb->last_error}\n" );
			exit( 1 );
		}

		$link_ids[] = (int) $wpdb->insert_id;
		$anchors[]  = sprintf(
			'<p>Link %1$d: <a href="%2$s">loop link %3$s %1$d</a></p>',
			$index + 1,
			esc_url( $url ),
			$label
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_title'   => "IAWMLF E2E Loop {$label}",
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_content' => implode( "\n", $anchors ),
		)
	);

	if ( is_wp_error( $post_id ) || 0 === $post_id ) {
		fwrite( STDERR, "wp_insert_post failed for {$label}\n" );
		exit( 1 );
	}

	// Pin the link list rather than relying on save_post having processed it.
	update_post_meta( (int) $post_id, Settings::LINK_META_KEY, $link_ids );

	$post_ids[] = (int) $post_id;

	echo "POST_{$label}_ID={$post_id}\n";
}

update_option( IAWMLF_E2E_QUERY_LOOP_POSTS, $post_ids );

echo 'HOME_URL=' . home_url( '/' ) . "\n";
echo 'SEEDED=' . count( $post_ids ) . "\n";
