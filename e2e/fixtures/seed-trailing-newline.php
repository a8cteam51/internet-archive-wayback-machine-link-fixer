<?php
/**
 * E2E seeder for issue #346: the link data span must not change how WordPress
 * formats the content around it.
 *
 * Seeds four Classic Editor posts, one per way the content can end. None of
 * them contain block delimiters, which is what keeps wpautop in the the_content
 * chain: do_blocks() unhooks wpautop for anything with blocks in it, so a block
 * editor post could never show this bug.
 *
 *   TRAILING_NEWLINE  ends with a newline          <-- the reported bug
 *   BLANK_LINES       ends with two blank lines
 *   BLOCK_LEVEL       ends with a closing </ul>
 *   AUTHORED_BREAK    has a single newline mid-paragraph, which must survive
 *
 * Every post ends with a unique marker word so the spec can find its last
 * paragraph without depending on the theme's markup.
 *
 * Run via: wp eval-file e2e/fixtures/seed-trailing-newline.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/trailing-newline-shared.php';

// Back up every option we are about to overwrite, so the cleaner can put them back.
$backup = array();
foreach ( iawmlf_e2e_trailing_newline_options() as $option ) {
	$backup[ $option ] = get_option( $option, null );
}
update_option( IAWMLF_E2E_TRAILING_NEWLINE_BACKUP, $backup );

// The span is only rendered when the fixer is doing something with links.
update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

// An exclusion pattern matching our URL would send us down the wrong code path.
update_option( Settings::LINK_EXCLUSIONS, array() );

$link = '<a href="https://example.com/iawmlf-e2e-346">an example link</a>';

$shapes = array(
	// key => array( marker, content ). The content is raw, exactly as the
	// Classic Editor's Code view would store it.
	'TRAILING_NEWLINE' => array(
		'IAWMLFENDONE',
		"Opening paragraph with {$link}.\n\nLast word IAWMLFENDONE\n",
	),
	'BLANK_LINES'      => array(
		'IAWMLFENDTWO',
		"Opening paragraph with {$link}.\n\nLast word IAWMLFENDTWO\n\n\n",
	),
	'BLOCK_LEVEL'      => array(
		'IAWMLFENDTHREE',
		"Opening paragraph with {$link}.\n\n<ul>\n<li>One</li>\n<li>Last word IAWMLFENDTHREE</li>\n</ul>\n",
	),
	'AUTHORED_BREAK'   => array(
		'IAWMLFENDFOUR',
		"Opening paragraph with {$link}.\n\nFirst line\nLast word IAWMLFENDFOUR\n",
	),
);

$post_ids = array();

foreach ( $shapes as $key => $shape ) {
	list( $marker, $content ) = $shape;

	$post_id = wp_insert_post(
		array(
			'post_title'   => "IAWMLF E2E 346 {$key}",
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_content' => $content,
		)
	);

	if ( is_wp_error( $post_id ) || 0 === $post_id ) {
		fwrite( STDERR, "wp_insert_post failed for {$key}\n" );
		exit( 1 );
	}

	// Record the post's links, so there is a payload span to render.
	( new WP_Post_Controller() )->process_links_in_content( (int) $post_id );

	$post_ids[] = (int) $post_id;

	echo "{$key}_URL=" . get_permalink( $post_id ) . "\n";
	echo "{$key}_MARKER={$marker}\n";
}

update_option( IAWMLF_E2E_TRAILING_NEWLINE_POSTS, $post_ids );

echo 'SEEDED=' . count( $post_ids ) . "\n";
