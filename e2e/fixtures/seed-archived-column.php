<?php
/**
 * E2E seeder: the "Last Archived" column on the posts list screen.
 *
 * Sets up everything the spec needs, and does not depend on any other spec
 * having run first:
 *
 *   - Onboarding is marked complete, or every admin page redirects to the wizard.
 *   - 'post' is an auto archived post type, so the column is registered.
 *   - Three posts: one with a known archive date, one never archived, and one
 *     excluded from auto archiving.
 *
 * Anything overwritten is stashed so clean-archived-column.php can restore it.
 *
 * Run via: wp eval-file e2e/fixtures/seed-archived-column.php
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

if ( ! class_exists( Settings::class ) ) {
	fwrite( STDERR, "Plugin not loaded, is internet-archive-wayback-machine-link-fixer active?\n" );
	exit( 1 );
}

require_once __DIR__ . '/archived-column-shared.php';

// Stash the options this seeder overwrites, so the state can be put back.
$backup = array();
foreach ( iawmlf_e2e_archived_column_options() as $option ) {
	$backup[ $option ] = get_option( $option, null );
}
update_option( IAWMLF_E2E_ARCHIVED_COLUMN_BACKUP, $backup );

// Onboarding must be complete or every admin page redirects to the setup wizard.
update_option( Settings::POST_ACTIVATION_ONBOARDING_KEY, Settings::ONBOARDING_COMPLETED_OPTION );
update_option( Settings::SETUP_WIZARD_COMPLETED_KEY, true );
update_option( Settings::SETUP_WIZARD_STEP_KEY, 'complete' );

// The column only appears for post types that get auto archived.
update_option( Settings::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post' ) );

/**
 * Creates a published post for the spec.
 *
 * @param string $title The post title.
 *
 * @return int
 */
function iawmlf_e2e_archived_column_create_post( string $title ): int {
	$post_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => 'IAWMLF e2e archived column probe.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, 'Could not create post: ' . $post_id->get_error_message() . "\n" );
		exit( 1 );
	}

	return (int) $post_id;
}

$archived_id = iawmlf_e2e_archived_column_create_post( 'IAWMLF e2e archived post' );
$never_id    = iawmlf_e2e_archived_column_create_post( 'IAWMLF e2e never archived post' );
$excluded_id = iawmlf_e2e_archived_column_create_post( 'IAWMLF e2e excluded post' );

update_post_meta( $archived_id, Settings::OWN_LINK_LAST_PROCESSED, IAWMLF_E2E_ARCHIVED_COLUMN_TIMESTAMP );

// The excluded post has a date too, to prove exclusion wins over it.
update_post_meta( $excluded_id, Settings::OWN_LINK_LAST_PROCESSED, IAWMLF_E2E_ARCHIVED_COLUMN_TIMESTAMP );
update_option( Settings::AUTO_ARCHIVER_EXCLUDED_POSTS, array( $excluded_id ) );

// Remember what to delete on clean up.
update_option( IAWMLF_E2E_ARCHIVED_COLUMN_POSTS, array( $archived_id, $never_id, $excluded_id ) );

// Output for the playwright spec. Each on its own line, KEY=VALUE.
echo 'ARCHIVED_POST_ID=' . $archived_id . "\n";
echo 'NEVER_POST_ID=' . $never_id . "\n";
echo 'EXCLUDED_POST_ID=' . $excluded_id . "\n";
echo 'EXPECTED_DATE=' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), IAWMLF_E2E_ARCHIVED_COLUMN_TIMESTAMP ) . "\n";
