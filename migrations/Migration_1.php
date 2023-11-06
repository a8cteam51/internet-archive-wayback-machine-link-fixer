<?php

/**
 * Migration 1
 *
 * Created: 17 Oct 2023
 * Iteration: 1
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Migration;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

/**
 * Migration 1
 */
class Migration_1 extends Abstract_Migration {

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

		// Create the report table.
		$report_table_name = $wpdb->prefix . Settings::SCAN_REPORT_TABLE_NAME;
		$report_sql        = "CREATE TABLE $report_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			report_id varchar(36) NOT NULL,
			user_id bigint(20) NOT NULL,
			blog_id bigint(20) NOT NULL,
			fixed int(1) NOT NULL DEFAULT 0,
			process varchar(20) NOT NULL,
			description text NULL,
			create_date datetime NOT NULL,
			completed_date datetime NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Create the log table.
		$log_table_name = $wpdb->prefix . Settings::SCAN_LOG_TABLE_NAME;

		$log_sql = "CREATE TABLE $log_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			report_id varchar(36) NOT NULL,
			post_id bigint(20) NOT NULL,
			links longtext NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $report_sql );
		dbDelta( $log_sql );
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

		$log_table_name = $wpdb->prefix . Settings::SCAN_LOG_TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS $log_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.

		// Drop the report table.
		$report_table_name = $wpdb->prefix . Settings::SCAN_REPORT_TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS $report_table_name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.
	}
}
