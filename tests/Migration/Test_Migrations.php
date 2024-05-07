<?php

/**
 * Unit tests for the migations
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_3;

/**
 * Test_Migrations
 */
class Test_Migrations extends \WP_UnitTestCase {

	/**
	 * @testdox [V1.2.0] There should be 1 table created for the links.
	 *
	 * @return void
	 */
	public function test_v1_2_0_migrations(): void {
		global $wpdb;

		$table = Settings::get_link_table_name();

		// Check the table exists.
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		// Check the columns exist.
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'id' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'url' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'archived' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'redirect_url' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'is_broken' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'checks' ) ) );

		// Trigger the down process.
		( new Migration_3() )->down();

		// Get last query from wpdb
		$query = $wpdb->last_query;

		// Check the table should have been dropped.
		$this->assertEquals( 'DROP TEMPORARY TABLE IF EXISTS ' . $table, $query );
	}
}
