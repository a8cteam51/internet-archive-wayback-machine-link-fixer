<?php

declare(strict_types=1);

/**
 * Tests for Logable_WPDB
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.6
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\WP\Logable_WPDB;

class Test_Logable_WPDB extends \WP_UnitTestCase {

	/** @testdox It should be possible to do a WPDB::prepare(), have the results logged and then a simple, sprintf returned (this allows to be used with other queries) */
	public function test_prepare(): void {
		$wpdb = new Logable_WPDB();

		$prepared = $wpdb->prepare( '--%d', array( 1 ) );

		$this->assertSame( '--1', $prepared );

		$this->assertArrayHasKey( 'prepare', $wpdb->usage_log );
		$this->assertSame( '--%d', $wpdb->usage_log['prepare'][0]['query'] );
		$this->assertSame( array( 1 ), $wpdb->usage_log['prepare'][0]['args'] );
	}

	/** @testdox It should be possible to do a insert() call and have the args logged and a defined value returned. */
	public function test_insert(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 24;

		$table  = 'foo';
		$data   = array( 'foo' => 'bar' );
		$format = array( '%s' );

		$result = $wpdb->insert( $table, $data, $format );

		// Test return value.
		$this->assertEquals( 24, $result );

		// Test been logged.
		$this->assertArrayHasKey( 'insert', $wpdb->usage_log );
		$this->assertArrayHasKey( $table, $wpdb->usage_log['insert'] );
		$this->assertSame( $data, $wpdb->usage_log['insert'][ $table ][0]['data'] );
		$this->assertSame( $format, $wpdb->usage_log['insert'][ $table ][0]['format'] );
	}

	/** @testdox It should be possible to do a get_results() call and have the args logged and a defined value returned. */
	public function test_get_results(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 789;

		$query  = 'MOCK QUERY';
		$output = 'OBJECT';

		$result = $wpdb->get_results( $query, $output );

		// Test return value.
		$this->assertEquals( 789, $result );

		$this->assertArrayHasKey( 'get_results', $wpdb->usage_log );
		$this->assertSame( $query, $wpdb->usage_log['get_results'][0]['query'] );
		$this->assertSame( $output, $wpdb->usage_log['get_results'][0]['output'] );
	}

	/** @testdox It should be possible to do a get_row() call and have the args logged and a defined value returned. */
	public function test_get_row(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 75;

		$query  = 'MOCK QUERY';
		$output = 'OBJECT';
		$y      = 10;

		$result = $wpdb->get_row( $query, $output, $y );

		// Test return value.
		$this->assertEquals( 75, $result );

		$this->assertArrayHasKey( 'get_row', $wpdb->usage_log );
		$this->assertSame( $query, $wpdb->usage_log['get_row'][0]['query'] );
		$this->assertSame( $output, $wpdb->usage_log['get_row'][0]['output'] );
		$this->assertSame( $y, $wpdb->usage_log['get_row'][0]['y'] );
	}

	/** @testdox It should be possible to do a get_col() call and have the args logged and a defined value returned. */
	public function test_get_col(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 75;

		$query = 'MOCK QUERY';
		$x     = 85;

		$result = $wpdb->get_col( $query, $x );

		// Test return value.
		$this->assertEquals( 75, $result );

		$this->assertArrayHasKey( 'get_col', $wpdb->usage_log );
		$this->assertSame( $query, $wpdb->usage_log['get_col'][0]['query'] );
		$this->assertSame( $x, $wpdb->usage_log['get_col'][0]['x'] );
	}

	/** @testdox It should be possible to do a get_var() call and have the args logged and a defined value returned. */
	public function test_get_var(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 123;

		$query = 'MOCK QUERY';
		$x     = 85;
		$y     = 45;

		$result = $wpdb->get_var( $query, $x, $y );

		// Test return value.
		$this->assertEquals( 123, $result );

		$this->assertArrayHasKey( 'get_var', $wpdb->usage_log );
		$this->assertSame( $query, $wpdb->usage_log['get_var'][0]['query'] );
		$this->assertSame( $x, $wpdb->usage_log['get_var'][0]['x'] );
		$this->assertSame( $y, $wpdb->usage_log['get_var'][0]['y'] );
	}

	/** @testdox It should be possible to do a query() call and have the args logged and a defined value returned. */
	public function test_query(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 123;

		$query = 'MOCK QUERY';

		$result = $wpdb->query( $query );

		// Test return value.
		$this->assertEquals( 123, $result );

		$this->assertArrayHasKey( 'query', $wpdb->usage_log );
		$this->assertSame( $query, $wpdb->usage_log['query'][0] );
	}

	/** @testdox It should be possible to do a replace() call and have the args logged and a defined value returned. */
	public function test_replace(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 24;

		$table  = 'foo';
		$data   = array( 'foo' => 'bar' );
		$format = array( '%s' );

		$result = $wpdb->replace( $table, $data, $format );

		// Test return value.
		$this->assertEquals( 24, $result );

		// Test been logged.
		$this->assertArrayHasKey( 'replace', $wpdb->usage_log );
		$this->assertArrayHasKey( $table, $wpdb->usage_log['replace'] );
		$this->assertSame( $data, $wpdb->usage_log['replace'][ $table ][0]['data'] );
		$this->assertSame( $format, $wpdb->usage_log['replace'][ $table ][0]['format'] );
	}

	/** @testdox It should be possible to do a update() call and have the args logged and a defined value returned. */
	public function test_update(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 24;

		$table        = 'foo';
		$data         = array( 'foo' => 'bar' );
		$where        = 'bar = baz';
		$format       = array( '%s' );
		$where_format = array( '%s' );

		$result = $wpdb->update( $table, $data, $where, $format, $where_format );

		// Test return value.
		$this->assertEquals( 24, $result );

		// Test been logged.
		$this->assertArrayHasKey( 'update', $wpdb->usage_log );
		$this->assertArrayHasKey( $table, $wpdb->usage_log['update'] );
		$this->assertSame( $data, $wpdb->usage_log['update'][ $table ][0]['data'] );
		$this->assertSame( $where, $wpdb->usage_log['update'][ $table ][0]['where'] );
		$this->assertSame( $format, $wpdb->usage_log['update'][ $table ][0]['format'] );
		$this->assertSame( $format, $wpdb->usage_log['update'][ $table ][0]['where_format'] );

	}

	/** @testdox It should be possible to do a delete() call and have the args logged and a defined value returned. */
	public function test_delete(): void {
		$wpdb              = new Logable_WPDB();
		$wpdb->then_return = 24;

		$table        = 'foo';
		$where        = 'bar = baz';
		$where_format = array( '%s' );

		$result = $wpdb->delete( $table, $where, $where_format );

		// Test return value.
		$this->assertEquals( 24, $result );

		// Test been logged.
		$this->assertArrayHasKey( 'delete', $wpdb->usage_log );
		$this->assertArrayHasKey( $table, $wpdb->usage_log['delete'] );
		$this->assertSame( $where, $wpdb->usage_log['delete'][ $table ][0]['where'] );
		$this->assertSame( $where_format, $wpdb->usage_log['delete'][ $table ][0]['where_format'] );

	}
}
