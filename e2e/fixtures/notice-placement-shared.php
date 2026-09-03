<?php
/**
 * Shared names for the admin notice placement seeder and its clean up.
 *
 * Loaded by seed-notice-placement.php and clean-notice-placement.php.
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

const IAWMLF_E2E_NOTICE_PLACEMENT_ACTION  = 'iawmlf_e2e_placement';
const IAWMLF_E2E_NOTICE_PLACEMENT_KEY     = 'e2eplacement';
const IAWMLF_E2E_NOTICE_PLACEMENT_MESSAGE = 'IAWMLF e2e notice placement probe.';
const IAWMLF_E2E_NOTICE_PLACEMENT_BACKUP  = 'iawmlf_e2e_notice_placement_backup';

/**
 * The options the seeder overwrites, and the clean up restores.
 *
 * @return string[]
 */
function iawmlf_e2e_notice_placement_options(): array {
	return array(
		Settings::POST_ACTIVATION_ONBOARDING_KEY,
		Settings::SETUP_WIZARD_COMPLETED_KEY,
		Settings::SETUP_WIZARD_STEP_KEY,
		Settings::ARCHIVE_ORG_ACCESS_KEY,
		Settings::ARCHIVE_ORG_SECRET_KEY,
	);
}

/**
 * The notification cache transient name, mirroring
 * List_Table_Action_Notification_Cache::compile_cache_key().
 *
 * @param int $user_id The admin user id.
 *
 * @return string
 */
function iawmlf_e2e_notice_placement_transient( int $user_id ): string {
	return 'iawmlf_list_table_action_cache_' . $user_id . '_'
		. IAWMLF_E2E_NOTICE_PLACEMENT_ACTION . '_' . IAWMLF_E2E_NOTICE_PLACEMENT_KEY;
}
