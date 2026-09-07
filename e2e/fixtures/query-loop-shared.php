<?php
/**
 * Shared constants for the query loop link data seeder and cleaner.
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

// Where the seeder stashes the previous value of every option it overwrites.
if ( ! defined( 'IAWMLF_E2E_QUERY_LOOP_BACKUP' ) ) {
	define( 'IAWMLF_E2E_QUERY_LOOP_BACKUP', 'iawmlf_e2e_query_loop_backup' );
}

// Where the seeder stashes the IDs of the posts it created.
if ( ! defined( 'IAWMLF_E2E_QUERY_LOOP_POSTS' ) ) {
	define( 'IAWMLF_E2E_QUERY_LOOP_POSTS', 'iawmlf_e2e_query_loop_posts' );
}

if ( ! function_exists( 'iawmlf_e2e_query_loop_posts' ) ) {
	/**
	 * Label => the outbound URLs that post links to.
	 *
	 * Two links each, all six distinct, so the spec can prove a post's span
	 * carries all of that post's links and none of any other post's.
	 *
	 * @return array<string, array<int, string>>
	 */
	function iawmlf_e2e_query_loop_posts(): array {
		return array(
			'A' => array(
				'https://example.com/iawmlf-e2e-loop-alpha-one',
				'https://example.com/iawmlf-e2e-loop-alpha-two',
			),
			'B' => array(
				'https://example.com/iawmlf-e2e-loop-bravo-one',
				'https://example.com/iawmlf-e2e-loop-bravo-two',
			),
			'C' => array(
				'https://example.com/iawmlf-e2e-loop-charlie-one',
				'https://example.com/iawmlf-e2e-loop-charlie-two',
			),
		);
	}
}

if ( ! function_exists( 'iawmlf_e2e_query_loop_options' ) ) {
	/**
	 * Every option the seeder overwrites, and so every option the cleaner restores.
	 *
	 * @return array<int, string>
	 */
	function iawmlf_e2e_query_loop_options(): array {
		return array(
			Settings::FIXER_OPTION,
			Settings::LINK_EXCLUSIONS,
		);
	}
}
