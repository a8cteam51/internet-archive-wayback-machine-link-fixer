<?php

/**
 * Migration 4
 *
 * Created: 3rd June 2024
 * Iteration: 4
 *
 * @since 1.2.1
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Migration;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

/**
 * Migration 4
 */
class Migration_4 extends Abstract_Migration {

	/**
	 * Run when the table is created.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function up(): void {
		// Create the report table.
		global $wpdb;

		// Create the link cache table.
		$link_cache_table_name = Settings::get_link_table_name();

		// Add an additional column called message
		$wpdb->query( "ALTER TABLE $link_cache_table_name ADD COLUMN message longtext" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name
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
