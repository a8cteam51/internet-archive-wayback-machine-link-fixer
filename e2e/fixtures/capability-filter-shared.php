<?php
/**
 * Shared constants for the reporting capability filter e2e fixtures. (S049)
 *
 * Included by seed-capability-filter.php and clean-capability-filter.php.
 */

// The editor account the spec logs in as.
if ( ! defined( 'IAWMLF_E2E_CAP_USER' ) ) {
	define( 'IAWMLF_E2E_CAP_USER', 'iawmlf_e2e_cap_editor' );
}

if ( ! defined( 'IAWMLF_E2E_CAP_PASS' ) ) {
	define( 'IAWMLF_E2E_CAP_PASS', 'iawmlf-e2e-cap-pass' );
}

// Flipped by the spec to turn the init-time filter on and off.
if ( ! defined( 'IAWMLF_E2E_CAP_FILTER_OPTION' ) ) {
	define( 'IAWMLF_E2E_CAP_FILTER_OPTION', 'iawmlf_e2e_cap_filter_enabled' );
}

// The must-use plugin that adds the filter, written by the seeder.
if ( ! defined( 'IAWMLF_E2E_CAP_MU_FILE' ) ) {
	define( 'IAWMLF_E2E_CAP_MU_FILE', WPMU_PLUGIN_DIR . '/iawmlf-e2e-capability-filter.php' );
}
