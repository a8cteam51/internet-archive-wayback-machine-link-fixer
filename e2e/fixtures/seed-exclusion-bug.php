<?php
/**
 * E2E seeder: per-link "Exclude this link" regression.
 *
 * Seeds a post containing two outbound links. Both are recorded in the
 * iawmlf links table as broken with an archived URL. They differ in
 * the `excluded` column:
 *
 *   - LINK_EXCLUDED  excluded=1   <-- the link under test
 *   - LINK_SENTINEL  excluded=0   <-- proves the front-end JS ran
 *
 * If the per-link exclusion is honoured, the front-end JS should leave
 * LINK_EXCLUDED's href / classes alone but still rewrite LINK_SENTINEL.
 * The test waits for LINK_SENTINEL to be rewritten, then asserts
 * LINK_EXCLUDED is untouched.
 *
 * Run via: wp eval-file e2e/fixtures/seed-exclusion-bug.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded — is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

global $wpdb;

$excluded_url  = 'https://example.com/iawmlf-e2e-excluded';
$sentinel_url  = 'https://example.com/iawmlf-e2e-sentinel';
$archived_pre  = 'https://web.archive.org/web/2024/';
$post_slug     = 'iawmlf-e2e-exclusion';
$links_table   = Settings::get_link_table_name();
$recent_check  = gmdate( 'Y-m-d H:i:s' );

// Ensure the fixer is in "replace_link" mode so the front-end script enqueues.
update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

// Make sure global URL exclusions are empty (otherwise our URLs could match
// a pattern and we'd be testing the wrong code path).
update_option( Settings::LINK_EXCLUSIONS, array() );

// Clean up any prior runs (idempotent).
$wpdb->delete( $links_table, array( 'url' => $excluded_url ), array( '%s' ) );
$wpdb->delete( $links_table, array( 'url' => $sentinel_url ), array( '%s' ) );

$existing = get_posts(
	array(
		'name'        => $post_slug,
		'post_type'   => 'post',
		'post_status' => 'any',
		'numberposts' => 1,
	)
);
foreach ( $existing as $p ) {
	wp_delete_post( $p->ID, true );
}

/**
 * Insert a link row directly so we control the `excluded` flag.
 *
 * @return integer Inserted row id.
 */
$insert_link = function ( string $url, bool $excluded ) use ( $wpdb, $links_table, $archived_pre, $recent_check ): int {
	$checks = wp_json_encode(
		array(
			array( 'date' => $recent_check, 'http_code' => 404 ),
		)
	);

	$ok = $wpdb->insert(
		$links_table,
		array(
			'url'             => $url,
			'archived'        => $archived_pre . $url,
			'checks'          => $checks,
			'message'         => '',
			'redirect_url'    => '',
			'is_broken'       => 1,
			'excluded'        => $excluded ? 1 : 0,
			'archive_process' => 'done',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
	);

	if ( false === $ok ) {
		fwrite( STDERR, "Insert failed for {$url}: {$wpdb->last_error}\n" );
		exit( 1 );
	}

	return (int) $wpdb->insert_id;
};

$excluded_id = $insert_link( $excluded_url, true );
$sentinel_id = $insert_link( $sentinel_url, false );

// Create the post. Two anchors, distinguishable by visible text.
$content = sprintf(
	'<p>Excluded: <a href="%1$s">excluded link</a></p>'
	. '<p>Sentinel: <a href="%2$s">sentinel link</a></p>',
	esc_url( $excluded_url ),
	esc_url( $sentinel_url )
);

$post_id = wp_insert_post(
	array(
		'post_title'   => 'IAWMLF E2E Exclusion Test',
		'post_name'    => $post_slug,
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_content' => $content,
	)
);

if ( is_wp_error( $post_id ) || 0 === $post_id ) {
	fwrite( STDERR, "wp_insert_post failed\n" );
	exit( 1 );
}

// Force the post-meta link list regardless of whether save_post processed it.
update_post_meta( $post_id, Settings::LINK_META_KEY, array( $excluded_id, $sentinel_id ) );

// Output for the playwright spec. Each on its own line, KEY=VALUE.
echo "POST_URL=" . get_permalink( $post_id ) . "\n";
echo "POST_ID={$post_id}\n";
echo "EXCLUDED_LINK_ID={$excluded_id}\n";
echo "SENTINEL_LINK_ID={$sentinel_id}\n";
echo "EXCLUDED_URL={$excluded_url}\n";
echo "SENTINEL_URL={$sentinel_url}\n";
