<?php
/**
 * Shared constants for the issue #346 seeder and cleaner.
 *
 * Keeping the option names and the backed-up option list in one place stops the
 * two scripts drifting apart and leaving state behind between runs.
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

// Where the seeder stashes the previous value of every option it overwrites.
if ( ! defined( 'IAWMLF_E2E_TRAILING_NEWLINE_BACKUP' ) ) {
	define( 'IAWMLF_E2E_TRAILING_NEWLINE_BACKUP', 'iawmlf_e2e_346_backup' );
}

// Where the seeder stashes the IDs of the posts it created.
if ( ! defined( 'IAWMLF_E2E_TRAILING_NEWLINE_POSTS' ) ) {
	define( 'IAWMLF_E2E_TRAILING_NEWLINE_POSTS', 'iawmlf_e2e_346_posts' );
}

if ( ! function_exists( 'iawmlf_e2e_trailing_newline_options' ) ) {
	/**
	 * Every option the seeder overwrites, and so every option the cleaner restores.
	 *
	 * @return array<int, string>
	 */
	function iawmlf_e2e_trailing_newline_options(): array {
		return array(
			Settings::FIXER_OPTION,
			Settings::LINK_EXCLUSIONS,
		);
	}
}
