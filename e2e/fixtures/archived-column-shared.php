<?php
/**
 * Shared names for the "Last Archived" column seeder and its clean up.
 *
 * Loaded by seed-archived-column.php and clean-archived-column.php.
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

const IAWMLF_E2E_ARCHIVED_COLUMN_BACKUP = 'iawmlf_e2e_archived_column_backup';
const IAWMLF_E2E_ARCHIVED_COLUMN_POSTS  = 'iawmlf_e2e_archived_column_posts';

// 2025-01-02 03:04:05 UTC. Fixed so the spec can assert an exact rendered date.
const IAWMLF_E2E_ARCHIVED_COLUMN_TIMESTAMP = 1735787045;

/**
 * The options the seeder overwrites, and the clean up restores.
 *
 * @return string[]
 */
function iawmlf_e2e_archived_column_options(): array {
	return array(
		Settings::POST_ACTIVATION_ONBOARDING_KEY,
		Settings::SETUP_WIZARD_COMPLETED_KEY,
		Settings::SETUP_WIZARD_STEP_KEY,
		Settings::ALLOWED_OWN_CONTENT_POST_TYPES,
		Settings::AUTO_ARCHIVER_EXCLUDED_POSTS,
	);
}
