<?php

/**
 * The Settings access class.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Settings;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Settings
 */
class Settings {

	// Prefix.
	public const SETTINGS_PREFIX = 't51_wlf_';


	// Option keys
	public const POST_TYPES_OPTION_KEY        = self::SETTINGS_PREFIX . 'post_types';
	public const MIGRATIONS_KEY               = self::SETTINGS_PREFIX . 'migration_log';
	public const DROP_TABLES_ON_UNINSTALL_KEY = self::SETTINGS_PREFIX . 'drop_tables_uninstall';
	public const LINK_EXCLUSIONS              = self::SETTINGS_PREFIX . 'link_exclusions';
	public const SCAN_EXISTING_POSTS          = self::SETTINGS_PREFIX . 'scan_existing_posts';


	// Table names.
	public const SCAN_LOG_TABLE_NAME    = self::SETTINGS_PREFIX . 'scan_log';
	public const SCAN_REPORT_TABLE_NAME = self::SETTINGS_PREFIX . 'scan_report';
	public const SCAN_LINK_CACHE_TABLE  = self::SETTINGS_PREFIX . 'scan_link_cache';
	public const LINK_TABLE             = self::SETTINGS_PREFIX . 'link_archive';

	// Events
	public const RUNNER_EVENT = self::SETTINGS_PREFIX . 'event_runner';

	// Meta Keys
	public const LINK_META_KEY = self::SETTINGS_PREFIX . 'links';

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
	 * Get all post types which should be scanned.
	 *
	 * @since   1.0.0
	 *
	 * @return  string[]
	 */
	public static function get_post_types(): array {
		return array_map( 'esc_html', (array) get_option( self::POST_TYPES_OPTION_KEY, array( 'page', 'post' ) ) );
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
		return absint(
			apply_filters(
				'wlf_link_checker_timeout',
				5000
			)
		);
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
		return apply_filters( 'wlf_link_exclusions', $links );
	}

	/**
	 * Get the number of posts to process per batch.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public static function get_posts_per_batch(): int {
		$per_batch = apply_filters( 'wlf_posts_per_batch', 10 );

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
		return absint(
			apply_filters(
				'wlf_link_check_duration_in_days',
				7
			)
		);
	}

	/**
	 * Which HTTP Status codes are treated as valid.
	 *
	 * @since 1.2.0
	 *
	 * @return integer[]
	 */
	public static function get_valid_http_status_codes(): array {
		$codes = array( 200 );
		return (array) apply_filters( 'wlf_valid_http_status_codes', $codes );
	}

	/**
	 * Should existing posts be scanned?
	 *
	 * @since 1.2.0
	 *
	 * @return boolean
	 */
	public static function should_scan_existing_posts(): bool {
		return (bool) get_option( self::SCAN_EXISTING_POSTS, true );
	}
}
