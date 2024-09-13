<?php

/**
 * Unit tests for the migations
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Migrations;
use WPCOMSpecialProjects\Wayback_Link_Fixer_Migration\Migration_1;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Migration\Abstract_Migration;

/**
 * Test_Migrations
 */
class Test_Migrations extends \WP_UnitTestCase {

	/**
	 * Ensure all migrations are cleared before running tests.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// Clear all migrations.
		update_option( Settings::MIGRATIONS_KEY, array() );
		Migrations::$migrations = array();
	}

	/**
	 * @testdox [V1.2.0] There should be 1 table created for the links.
	 *
	 * This has been used to test the migrations after being squashed in v1.3.*
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
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'is_broken' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'checks' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'redirect_url' ) ) );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'message' ) ) );

		// Trigger the down process.
		( new Migration_1() )->down();

		// Get last query from wpdb
		$query = $wpdb->last_query;

		// Check the table should have been dropped.
		$this->assertEquals( 'DROP TEMPORARY TABLE IF EXISTS ' . $table, $query );
	}

	/**
	 * @testdox When the plugin is uninstalled, any posts with the link meta should have the meta cleared if set to clear.
	 *
	 * @return void
	 */
	public function test_clear_link_meta_on_uninstall_if_set_to_remove(): void {
		// Set the allowed posts types.
		update_option( Settings::ALLOWED_POST_TYPES, array( 'page', 'post' ) );

		// Create posts in both post types with meta.
		$post_id_1 = \WP_UnitTestCase_Base::factory()->post->create();
		$post_id_2 = \WP_UnitTestCase_Base::factory()->post->create( array( 'post_type' => 'page' ) );

		// Add the meta.
		update_post_meta( $post_id_1, Settings::LINK_META_KEY, array( 1,2,3 ) );
		update_post_meta( $post_id_2, Settings::LINK_META_KEY, array( 4,5,6 ) );

		// Enable the drop tables on uninstall.
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, true );

		// Trigger the down process.
		Migrations::down();

		// Check the meta has been removed.
		$this->assertEmpty( get_post_meta( $post_id_1, Settings::LINK_META_KEY ) );
		$this->assertEmpty( get_post_meta( $post_id_2, Settings::LINK_META_KEY ) );

	}

	/**
	 * @testdox When the plugin is uninstalled, any posts with the link meta should have the meta retained if set to keep.
	 *
	 * @return void
	 */
	public function test_keep_link_meta_on_uninstall_if_set_to_keep(): void {
		// Set the allowed posts types.
		update_option( Settings::ALLOWED_POST_TYPES, array( 'page', 'post' ) );

		// Create posts in both post types with meta.
		$post_id_1 = \WP_UnitTestCase_Base::factory()->post->create();
		$post_id_2 = \WP_UnitTestCase_Base::factory()->post->create( array( 'post_type' => 'page' ) );

		// Add the meta.
		update_post_meta( $post_id_1, Settings::LINK_META_KEY, array( 1,2,3 ) );
		update_post_meta( $post_id_2, Settings::LINK_META_KEY, array( 4,5,6 ) );

		// Disable the drop tables on uninstall.
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, false );

		// Trigger the down process.
		Migrations::down();

		// Check the meta has been removed.
		$this->assertNotEmpty( get_post_meta( $post_id_1, Settings::LINK_META_KEY ) );
		$this->assertNotEmpty( get_post_meta( $post_id_2, Settings::LINK_META_KEY ) );
	}

	/**
	 * @testdox When the plugin is uninstalled, table should be dropped if set to drop.
	 *
	 * @return void
	 */
	public function test_drop_tables_on_uninstall_if_set_to(): void {
		// Enable the drop tables on uninstall.
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, true );

		$migration = $this->createMock(Abstract_Migration::class);
		$migration_class = get_class($migration);

		// Add to the previously run migrations.
		Settings::update_migrations( array( $migration_class ) );

		// Add the migration to the list of migrations
		Migrations::$migrations[] = $migration_class;


		// Trigger the down process.
		Migrations::down();

		// Check there are no migrations.
		$this->assertEmpty( Settings::migrations() );
	}

	/**
	 * @testdox When the plugin is uninstalled, table should not be dropped if set to not drop.
	 *
	 * @return void
	 */
	public function test_not_drop_tables_on_uninstall_if_set_to(): void {
		// Disable the drop tables on uninstall.
		update_option( Settings::DROP_TABLES_ON_UNINSTALL_KEY, false );

		$migration = $this->createMock(Abstract_Migration::class);
		$migration_class = get_class($migration);

		// Add to the previously run migrations.
		Settings::update_migrations( array( $migration_class ) );

		// Add the migration to the list of migrations
		Migrations::$migrations[] = $migration_class;

		// Trigger the down process.
		Migrations::down();

		// Check the migration is still in the list.
		$this->assertNotEmpty( Settings::migrations() );
		$this->assertContains($migration_class, Settings::migrations());
	}

	/**
	 * @testdox Ensure that when migrations are run, there is a column called 'excluded' and it should be not null with a default of true.
	 *
	 * @return void
	 */
	public function test_migration_2(): void {
		global $wpdb;

		$table = Settings::get_link_table_name();

		// Check the column exists.
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'excluded' ) ) );

		// Get the column details.
		$details = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM $table LIKE %s", 'excluded' ));

		// Check the column is not null.
		$this->assertEquals( 'NO', $details[0]->Null );
		$this->assertEquals( '0', $details[0]->Default );
		$this->assertEquals( 'tinyint(1)', $details[0]->Type );


	}

}
