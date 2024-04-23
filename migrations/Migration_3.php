<?php

/**
 * Migration 3
 *
 * Created: 6th March 2024
 * Iteration: 2
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Migration;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

/**
 * Migration 3
 */
class Migration_3 extends Abstract_Migration {

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

		// Create the link cache table.
		$link_cache_table_name = Settings::get_link_table_name();

		// Add an additional column after url called redirect_url
		$wpdb->query( "ALTER TABLE $link_cache_table_name ADD COLUMN redirect_url longtext" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name
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
