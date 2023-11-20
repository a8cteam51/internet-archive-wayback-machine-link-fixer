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
	private const SETTINGS_PREFIX = 't51_wlf_';

	// Option keys
	public const POST_TYPES_OPTION_KEY        = self::SETTINGS_PREFIX . 'post_types';
	public const DROP_TABLES_ON_UNINSTALL_KEY = self::SETTINGS_PREFIX . 'drop_tables_uninstall';
	public const MIGRATIONS_KEY               = self::SETTINGS_PREFIX . 'migration_log';
	public const LINK_CHECKER_TIMEOUT         = self::SETTINGS_PREFIX . 'link_checker_timeout';
	public const HTTP_STATUS_CODES            = self::SETTINGS_PREFIX . 'http_status_codes';
	public const LINK_CACHE_EXPIRATION        = self::SETTINGS_PREFIX . 'link_cache_expiration';
	public const LINK_EXCLUSIONS              = self::SETTINGS_PREFIX . 'link_exclusions';

	## Table names.
	public const SCAN_LOG_TABLE_NAME    = self::SETTINGS_PREFIX . 'scan_log';
	public const SCAN_REPORT_TABLE_NAME = self::SETTINGS_PREFIX . 'scan_report';
	public const SCAN_LINK_CACHE_TABLE  = self::SETTINGS_PREFIX . 'scan_link_cache';


	/**
	 * Get all post types which should be scanned.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
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
		return get_option( self::MIGRATIONS_KEY, array() );
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
		update_option( self::MIGRATIONS_KEY, $migrations );
	}

	/**
	 * Get the link checker timeout in MS
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public static function get_link_checker_timeout(): int {
		return absint( get_option( self::LINK_CHECKER_TIMEOUT, 1000 ) );
	}

	/**
	 * Gets the list of all HTTP status to look for.
	 * As comma separated string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_http_status_codes(): string {
		return sanitize_text_field( (string) get_option( self::HTTP_STATUS_CODES, '404,410,500,502,300,301,303' ) );
	}

	/**
	 * Get the link cache expiry (in seconds.)
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public static function get_link_cache_expiration(): int {
		return absint( get_option( self::LINK_CACHE_EXPIRATION, DAY_IN_SECONDS ) );
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

		return apply_filter( 't51_wlf_link_exclusions', $links );
	}
}
