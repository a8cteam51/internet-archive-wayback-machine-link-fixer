<?php

/**
 * Migration 1
 *
 * Created: 12 Aug 2024
 * Iteration: 1
 *
 * @since 1.3.0
 *
 * This is a merge of the development migrations into a single migration.
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

		// Create the link cache table.
		$link_cache_table_name = Settings::get_link_table_name();

		$link_cache_sql = "CREATE TABLE $link_cache_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url longtext NOT NULL,
			archived longtext,
			is_broken tinyint(1) NOT NULL DEFAULT 0,
			checks JSON NOT NULL,
			message longtext,
			redirect_url longtext,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $link_cache_sql );
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
		$link_table = Settings::get_link_table_name();
		$wpdb->query( "DROP TABLE IF EXISTS $link_table" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name.
	}
}
