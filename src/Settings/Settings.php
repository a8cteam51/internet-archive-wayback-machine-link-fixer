<?php

/**
 * The Settings access class.
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Settings;

use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Environmental;
use Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Abstract_Migration;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Archive_Services_Online_Event;

defined( 'ABSPATH' ) || exit;

/**
 * Settings
 */
class Settings {

	// Prefix.
	public const SETTINGS_PREFIX = 'iawmlf_';


	// Option keys
	public const PROCESS_LINKS                  = self::SETTINGS_PREFIX . 'process_links';
	public const ALLOWED_POST_TYPES             = self::SETTINGS_PREFIX . 'post_types';
	public const MIGRATIONS_KEY                 = self::SETTINGS_PREFIX . 'migration_log';
	public const DROP_TABLES_ON_UNINSTALL_KEY   = self::SETTINGS_PREFIX . 'drop_tables_uninstall';
	public const LINK_EXCLUSIONS                = self::SETTINGS_PREFIX . 'link_exclusions';
	public const LINK_FIXER_EXCLUDED_POSTS      = self::SETTINGS_PREFIX . 'link_fixer_excluded_posts';
	public const SCAN_EXISTING_POSTS            = self::SETTINGS_PREFIX . 'scan_existing_posts';
	public const ARCHIVE_ORG_SECRET_KEY         = self::SETTINGS_PREFIX . 'archive_api_secret';
	public const ARCHIVE_ORG_ACCESS_KEY         = self::SETTINGS_PREFIX . 'archive_api_access';
	public const FIXER_OPTION                   = self::SETTINGS_PREFIX . 'fixer_option';
	public const ARCHIVE_ORG_STATUS_KEY         = self::SETTINGS_PREFIX . 'archive_api_status';
	public const ARCHIVE_ORG_CREDS_VALID_KEY    = self::SETTINGS_PREFIX . 'archive_api_creds_valid';
	public const MINIMUM_CHECKS_BEFORE_BROKEN   = self::SETTINGS_PREFIX . 'failed_count';
	public const LINK_CHECK_DURATION_IN_DAYS    = self::SETTINGS_PREFIX . 'link_check_duration_in_days';
	public const POST_ACTIVATION_ONBOARDING_KEY = self::SETTINGS_PREFIX . 'post_activation_onboarding';
	public const SETUP_WIZARD_STEP_KEY          = self::SETTINGS_PREFIX . 'setup_wizard';
	public const SETUP_WIZARD_COMPLETED_KEY     = self::SETTINGS_PREFIX . 'setup_wizard_completed';
	public const ONBOARDING_DATE_KEY            = self::SETTINGS_PREFIX . 'onboarding_date';
	public const CAST_ARCHIVED_TO_HTTPS         = self::SETTINGS_PREFIX . 'cast_to_https';

	// Table names.
	public const LINK_TABLE = 'iawmlf_link_archive';


	// Meta Keys
	public const LINK_META_KEY           = self::SETTINGS_PREFIX . 'links';
	public const OWN_LINK_LAST_PROCESSED = self::SETTINGS_PREFIX . 'last_processed';

	// Fixer Options
	public const FIXER_OPTION_DO_NOTHING   = 'do_nothing';
	public const FIXER_OPTION_REPLACE_LINK = 'replace_link';

	// Onboarding options.
	public const ONBOARDING_COMPLETED_OPTION = self::SETTINGS_PREFIX . 'onboarding_completed';
	public const ONBOARDING_PENDING_OPTION   = self::SETTINGS_PREFIX . 'onboarding_pending';

	// Own content submissions.
	public const ALLOW_OWN_CONTENT_SUBMISSIONS             = self::SETTINGS_PREFIX . 'allow_own_content_submissions';
	public const ALLOWED_OWN_CONTENT_POST_TYPES            = self::SETTINGS_PREFIX . 'allowed_own_content_post_types';
	public const ROUTINELY_UPDATE_WAYBACK_MACHINE          = self::SETTINGS_PREFIX . 'routinely_update_wayback_machine';
	public const ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL = self::SETTINGS_PREFIX . 'routinely_update_wayback_machine_interval';

	/**
	 * Gets the link table name.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public static function get_link_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::LINK_TABLE;
	}

	/**
	 * Is the link processing enabled?
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $default_value Optional default value if not set. Default false.
	 *
	 * @return boolean
	 */
	public static function is_link_processing_enabled( bool $default_value = false ): bool {
		return (bool) get_option( self::PROCESS_LINKS, $default_value );
	}

	/**
	 * Get all post types which should be scanned.
	 *
	 * @since   1.0.0
	 *
	 * @return  string[]
	 */
	public static function get_allowed_post_types(): array {
		return array_map( 'esc_html', (array) get_option( self::ALLOWED_POST_TYPES, array( 'page', 'post' ) ) );
	}

	/**
	 * Should the tables be dropped when the plugin is deactivated?
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	public static function drop_tables_on_uninstall(): bool {
		return (bool) get_option( self::DROP_TABLES_ON_UNINSTALL_KEY, false );
	}

	/**
	 * Get the processed migrations.
	 *
	 * @since 0.1.0
	 *
	 * @return class-string<Abstract_Migration>[]
	 */
	public static function migrations(): array {
		return (array) get_option( self::MIGRATIONS_KEY, array() );
	}

	/**
	 * Update the migrations
	 *
	 * @since 0.1.0
	 *
	 * @param class-string<Abstract_Migration>[] $migrations The migrations to update.
	 *
	 * @return void
	 */
	public static function update_migrations( array $migrations ): void {
		update_option( self::MIGRATIONS_KEY, $migrations, false );
	}

	/**
	 * Get the link checker timeout in MS
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public static function get_link_checker_timeout(): int {
		return absint( apply_filters( 'iawmlf_link_checker_timeout', 5000 ) );
	}

	/**
	 * Get the array of link exclusions.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public static function get_link_exclusions(): array {
		$links = array_map( 'esc_html', (array) get_option( self::LINK_EXCLUSIONS, array() ) );
		return apply_filters( 'iawmlf_link_exclusions', $links );
	}

	/**
	 * Get the array of excluded post IDs for the link fixer.
	 *
	 * @since 1.4.0
	 *
	 * @return int[]
	 */
	public static function get_link_fixer_excluded_posts(): array {
		$post_ids = array_map( 'absint', (array) get_option( self::LINK_FIXER_EXCLUDED_POSTS, array() ) );
		return (array) apply_filters( 'iawmlf_link_fixer_excluded_posts', array_filter( $post_ids ) );
	}

	/**
	 * Get the number of posts to process per batch.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public static function get_posts_per_batch(): int {
		$per_batch = absint( apply_filters( 'iawmlf_posts_per_batch', 10 ) );

		// If value is less than or equal to 1, set as 2.
		return $per_batch <= 1 ? 2 : $per_batch;
	}

	/**
	 * Get the link check duration.
	 * In days
	 *
	 * @since 1.2.0
	 *
	 * @return integer
	 */
	public static function get_link_check_duration(): int {
		$duration = absint( get_option( self::LINK_CHECK_DURATION_IN_DAYS, 3 ) );
		return absint( apply_filters( 'iawmlf_link_check_duration_in_days', $duration ) );
	}

	/**
	 * Which HTTP Status codes are treated as valid.
	 *
	 * @since 1.2.0
	 *
	 * @return integer[]
	 */
	public static function get_valid_http_status_codes(): array {
		$codes = array( 200, 206, 429 );
		return (array) apply_filters( 'iawmlf_valid_http_status_codes', $codes );
	}

	/**
	 * Should existing posts be scanned?
	 *
	 * @since 1.2.0
	 *
	 * @param boolean $default_value Optional default value if not set. Default false.
	 *
	 * @return boolean
	 */
	public static function should_scan_existing_posts( bool $default_value = false ): bool {
		// If you can not process links, return false.
		if ( ! self::is_link_processing_enabled( $default_value ) ) {
			return false;
		}
		return (bool) get_option( self::SCAN_EXISTING_POSTS, $default_value );
	}

	/**
	 * How many times does a link need to be invalid before considered broken.
	 *
	 * @since 1.2.0
	 *
	 * @return integer
	 */
	public static function get_failed_count(): int {
		$retries = absint( get_option( self::MINIMUM_CHECKS_BEFORE_BROKEN, 3 ) );
		return absint( apply_filters( 'iawmlf_failed_count', $retries ) );
	}

	/**
	 * Get the archive.org API key.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public static function get_archive_secret_key(): string {
		return esc_attr( get_option( self::ARCHIVE_ORG_SECRET_KEY, '' ) );
	}

	/**
	 * Get the archive.org API secret.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public static function get_archive_access_key(): string {
		return esc_attr( get_option( self::ARCHIVE_ORG_ACCESS_KEY, '' ) );
	}

	/**
	 * Checks if the archive.org API is configured.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public static function is_archive_api_configured(): bool {
		return '' !== self::get_archive_secret_key() && '' !== self::get_archive_access_key();
	}

	/**
	 * Gets the current fixer option.
	 *
	 * @since 1.3.0
	 *
	 * @param string $default_value Optional default value if not set. Default FIXER_OPTION_REPLACE_LINK.
	 *
	 * @return string
	 */
	public static function get_fixer_option( string $default_value = self::FIXER_OPTION_REPLACE_LINK ): string {
		return esc_attr( get_option( self::FIXER_OPTION, $default_value ) );
	}

	/**
	 * Checks if the archive.org API is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public static function is_archive_api_online(): bool {
		return iawmlf_is_archive_api_online();
	}

	/**
	 * Get the archive api extended status.
	 *
	 * @since 1.3.0
	 *
	 * @return array|null
	 */
	public static function get_archive_api_status(): ?array {
		$status = get_transient( self::ARCHIVE_ORG_STATUS_KEY );

		// If we dont have a status, trigger a check.
		if ( false === $status ) {
			Check_Archive_Services_Online_Event::add_to_queue();
			return null;
		}

		return is_array( $status ) ? $status : null;
	}

	/**
	 * Checks if posts should be added to wayback machine on save.
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $default_value Optional default value if not set. Default false.
	 *
	 * @return boolean
	 */
	public static function add_own_links( bool $default_value = false ): bool {
		$allow = (bool) get_option( self::ALLOW_OWN_CONTENT_SUBMISSIONS, $default_value );

		// If not production, force false.
		if ( ! Environmental::is_production() ) {
			$allow = false;
		}

		return (bool) apply_filters(
			'iawmlf_add_own_content_to_wayback_machine',
			$allow
		);
	}

	/**
	 * Gets the post types whos posts should be added to the wayback machine.
	 *
	 * @since 1.3.0
	 *
	 * @return string[]
	 */
	public static function own_link_allowed_post_types(): array {
		return apply_filters(
			'iawmlf_own_content_post_types',
			array_map( 'esc_html', (array) get_option( self::ALLOWED_OWN_CONTENT_POST_TYPES, array( 'post', 'page' ) ) )
		);
	}

	/**
	 * Checks if posts should be routinely updated in the wayback machine.
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $default_value Optional default value if not set. Default false.
	 *
	 * @return boolean
	 */
	public static function own_link_routinely_update( bool $default_value = false ): bool {
		return (bool) apply_filters(
			'iawmlf_routinely_update_wayback_machine',
			(bool) get_option( self::ROUTINELY_UPDATE_WAYBACK_MACHINE, $default_value )
		);
	}

	/**
	 * Gets the interval between updates.
	 *
	 * @since 1.3.0
	 *
	 * @return integer Time in days.
	 */
	public static function own_link_routine_update_interval(): int {
		$default  = 28;
		$interval = absint(
			apply_filters(
				'iawmlf_routinely_update_wayback_machine_interval',
				get_option( self::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL, $default )
			)
		);

		return $interval <= 0 ? $default : $interval;
	}

	/**
	 * Should the link table, show additional data?
	 *
	 * This is for debugging purposes only.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public static function show_link_table_debug_data(): bool {
		return (bool) apply_filters( 'iawmlf_show_link_table_debug_data', false );
	}

	/**
	 * Check if we have valid credentials for the archive.org API.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public static function has_valid_archive_api_credentials(): bool {
		return (bool) get_option( self::ARCHIVE_ORG_CREDS_VALID_KEY, false ) === true;
	}

	/**
	 * Updates the archive.org API credentials validity.
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $valid True if the credentials are valid, false otherwise.
	 *
	 * @return void
	 */
	public static function update_archive_api_credentials_validity( bool $valid ): void {
		update_option( self::ARCHIVE_ORG_CREDS_VALID_KEY, $valid, false );
	}

	/**
	 * Gets the required capability for the reporting page.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public static function get_reporting_page_capability(): string {
		return apply_filters(
			'iawmlf_reporting_page_capability',
			'manage_options'
		);
	}

	/**
	 * Checks if the HTML link output should be rendered in the frontend.
	 *
	 * @since 1.3.1
	 *
	 * @return boolean
	 */
	public static function should_render_html_link_output(): bool {
		$allowed = in_array( self::get_fixer_option(), array( self::FIXER_OPTION_REPLACE_LINK ), true );

		/**
		 * Filter to allow or disallow the HTML link output in the frontend.
		 *
		 * @since 1.3.1
		 *
		 * @param boolean $allowed Whether the HTML link output should be rendered.
		 * @param string  $option  The current fixer option.
		 *
		 * @return boolean
		 */
		return (bool) apply_filters( 'iawmlf_should_render_html_link_output', $allowed, self::get_fixer_option() );
	}

	/**
	 * Get the current Wizard step
	 *
	 * @since 1.3.4
	 *
	 * @param string $default_value Optional default value if not set. Default 'step-1'.
	 *
	 * @return string
	 */
	public static function get_setup_wizard_step( string $default_value = 'step-1' ): string {
		return (string) get_option( self::SETUP_WIZARD_STEP_KEY, $default_value );
	}

	/**
	 * Sets the current Wizard step
	 *
	 * @since 1.3.4
	 *
	 * @param string $step The step to set.
	 *
	 * @return void
	 */
	public static function update_setup_wizard_step( string $step ): void {
		update_option( self::SETUP_WIZARD_STEP_KEY, $step );
	}

	/**
	 * Checks if the wizard has been completed.
	 *
	 * @since 1.3.4
	 *
	 * @return boolean
	 */
	public static function is_wizard_completed(): bool {
		return (bool) get_option( self::SETUP_WIZARD_COMPLETED_KEY, false );
	}

	/**
	 * Mark the wizard as completed or not.
	 *
	 * @since 1.3.4
	 *
	 * @param boolean $completed True if completed, false otherwise.
	 *
	 * @return void
	 */
	public static function set_wizard_completed( bool $completed ): void {
		update_option( self::SETUP_WIZARD_COMPLETED_KEY, $completed );
	}

	/**
	 * Gets the onboarding date.
	 *
	 * @since 1.3.4
	 *
	 * @return string|null
	 */
	public static function get_onboarding_date(): ?string {
		$date = get_option( self::ONBOARDING_DATE_KEY, null );
		return is_string( $date ) ? $date : null;
	}

	/**
	 * Sets the onboarding date.
	 *
	 * @since 1.3.4
	 *
	 * @param string $date The date to set.
	 *
	 * @return void
	 */
	public static function set_onboarding_date( string $date ): void {
		update_option( self::ONBOARDING_DATE_KEY, $date );
	}

	/**
	 * Sets the onboarding status.
	 *
	 * @since 1.3.4
	 *
	 * @param string $status The status to set.
	 *
	 * @return void
	 */
	public static function set_onboarding_status( string $status ): void {
		// If the passed status is not valid, set as pending.
		if ( ! in_array( $status, array( self::ONBOARDING_COMPLETED_OPTION, self::ONBOARDING_PENDING_OPTION ), true ) ) {
			$status = self::ONBOARDING_PENDING_OPTION;
		}

		update_option( self::POST_ACTIVATION_ONBOARDING_KEY, $status, false );
	}

	/**
	 * Get the current onboarding status.
	 *
	 * @since 1.3.4
	 *
	 * @param string $default_value Optional default value if not set. Default ONBOARDING_PENDING_OPTION.
	 *
	 * @return string
	 */
	public static function get_onboarding_status( string $default_value = self::ONBOARDING_COMPLETED_OPTION ): string {
		$state = get_option( self::POST_ACTIVATION_ONBOARDING_KEY, $default_value );
		return in_array( $state, array( self::ONBOARDING_COMPLETED_OPTION, self::ONBOARDING_PENDING_OPTION ), true )
			? $state
			: self::ONBOARDING_PENDING_OPTION;
	}

	/**
	 * Checks if archived links should be cast as HTTPS.
	 *
	 * @since 1.3.5
	 *
	 * @return boolean
	 */
	public static function should_cast_archived_to_https(): bool {
		return (bool) get_option( self::CAST_ARCHIVED_TO_HTTPS, false );
	}

	/**
	 * Clear all the options.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public static function clear_all_options(): void {
		// Clear all the options.
		delete_option( self::PROCESS_LINKS );
		delete_option( self::ALLOWED_POST_TYPES );
		delete_option( self::MIGRATIONS_KEY );
		delete_option( self::DROP_TABLES_ON_UNINSTALL_KEY );
		delete_option( self::LINK_EXCLUSIONS );
		delete_option( self::LINK_FIXER_EXCLUDED_POSTS );
		delete_option( self::SCAN_EXISTING_POSTS );
		delete_option( self::ARCHIVE_ORG_SECRET_KEY );
		delete_option( self::ARCHIVE_ORG_ACCESS_KEY );
		delete_option( self::FIXER_OPTION );
		delete_option( self::ARCHIVE_ORG_STATUS_KEY );
		delete_option( self::ARCHIVE_ORG_CREDS_VALID_KEY );
		delete_option( self::ALLOW_OWN_CONTENT_SUBMISSIONS );
		delete_option( self::ALLOWED_OWN_CONTENT_POST_TYPES );
		delete_option( self::ROUTINELY_UPDATE_WAYBACK_MACHINE );
		delete_option( self::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL );
		delete_option( self::POST_ACTIVATION_ONBOARDING_KEY );
		delete_option( self::MINIMUM_CHECKS_BEFORE_BROKEN );
		delete_option( self::LINK_CHECK_DURATION_IN_DAYS );
		delete_option( self::SETUP_WIZARD_STEP_KEY );
		delete_option( self::SETUP_WIZARD_COMPLETED_KEY );
		delete_option( self::ONBOARDING_DATE_KEY );
		delete_option( self::CAST_ARCHIVED_TO_HTTPS );
	}
}
