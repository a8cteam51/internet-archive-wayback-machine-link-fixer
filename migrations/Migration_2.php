<?php

/**
 * Migration 2
 *
 * Created: 6th March 2024
 * Iteration: 2
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Migration;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

/**
 * Migration 2
 */
class Migration_2 extends Abstract_Migration {

	/**
	 * Run when the table is created.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function up(): void {
		// Create the report table.
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Create the link cache table.
		$link_cache_table_name = Settings::LINK_TABLE;

		$link_cache_sql = "CREATE TABLE $link_cache_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url longtext NOT NULL,
			archived longtext,
			is_broken tinyint(1) NOT NULL DEFAULT 0,
			checks JSON NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $link_cache_sql );

		// Drop the old tables.
		$wpdb->query( 'DROP TABLE IF EXISTS ' . Settings::SCAN_LOG_TABLE_NAME ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.
		$wpdb->query( 'DROP TABLE IF EXISTS ' . Settings::SCAN_REPORT_TABLE_NAME ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . Settings::SCAN_LINK_CACHE_TABLE ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name;
	}

	/**
	 * Run when the table is dropped.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function down(): void {
		global $wpdb;

		// Drop the log table.

		$log_table_name = Settings::SCAN_LOG_TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS $log_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.

		// Drop the report table.
		$report_table_name = Settings::SCAN_REPORT_TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS $report_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.

		// Drop the link cache table.
		$link_cache_table_name = Settings::SCAN_LINK_CACHE_TABLE;
		$wpdb->query( "DROP TABLE IF EXISTS $link_cache_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.

		// Drop link table.
		$link_table_name = Settings::LINK_TABLE;
		$wpdb->query( "DROP TABLE IF EXISTS $link_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name;
	}
}
