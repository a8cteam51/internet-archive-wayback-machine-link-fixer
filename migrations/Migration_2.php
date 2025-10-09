<?php

/**
 * Migration 2
 *
 * Created: 13 Sep 2024
 * Iteration: 2
 *
 * @since 1.3.1
 *
 * Adds a new column to track if a link should be ignored.
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Migration;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Abstract_Migration;

defined( 'ABSPATH' ) || exit;

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

		// Create the link cache table.
		$link_cache_table_name = Settings::get_link_table_name();

		// Add an additional column to track if a link is excluded.
		$wpdb->query( "ALTER TABLE $link_cache_table_name ADD COLUMN excluded TINYINT(1) NOT NULL DEFAULT 0" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant due to table name
	}


	/**
	 * Run when the table is dropped.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function down(): void {
		// no-op
	}
}
