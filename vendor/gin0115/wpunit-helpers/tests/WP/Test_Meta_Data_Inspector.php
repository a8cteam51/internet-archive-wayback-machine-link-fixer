<?php

declare(strict_types=1);

/**
 * Tests for the meta box inspector
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\Tests\WP;

use Gin0115\WPUnit_Helpers\WP\Meta_Data_Inspector;

class Test_Meta_Data_Inspector extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		// Reset the registered meta data.
		$GLOBALS['wp_meta_keys'] = array();

		$this->register_mock_meta();
	}


	/**
	 * Registers all the mock post meta.
	 *
	 * @return void
	 */
	protected function register_mock_meta() {
		register_post_meta(
			'post',
			'post_meta_1',
			array(
				'default' => 'FALLBACK1',
				'single'  => true,
				'type'    => 'string',
			)
		);
		register_post_meta(
			'post',
			'post_meta_2',
			array(
				'default' => 'FALLBACK2',
				'single'  => true,
				'type'    => 'string',
			)
		);
		register_post_meta(
			'page',
			'page_meta_1',
			array(
				'default' => 'PAGE BACKUP',
				'single'  => true,
			)
		);
		register_term_meta( 'some_tax', 'term1', array( 'type' => 'float' ) );
		register_term_meta( 'some_tax', 'term2', array() );
		register_term_meta( 'other_tax', 'term3', array() );
		register_meta( 'user', 'user1', array() );
		register_meta( 'user', 'user2', array() );
		register_meta( 'comment', 'comment1', array() );
		register_meta( 'comment', 'comment2', array() );
	}

	/** @testdox Can create inspector from static contructor */
	public function test_can_construct_with_static_initalise() {
		$inspector = Meta_Data_Inspector::initialise();
		$this->assertInstanceOf( Meta_Data_Inspector::class, $inspector );
	}

	/** @testdox Can populate with registered meta fields. */
	public function test_can_populate_with_registered_meta() {

		// Manual constuctor.
		$inspector = new Meta_Data_Inspector();
		$inspector->set_registered_meta_data();
		$this->assertCount( 10, $inspector->registered_meta_data );
	}

	/** @testdox Can find post meta based on its key */
	public function test_find_post_meta(): void {
		$inspector = Meta_Data_Inspector::initialise();
		$post_meta = $inspector->find_post_meta( 'post', 'post_meta_2' );

		$this->assertNotNull( $post_meta );
		$this->assertEquals( 'string', $post_meta->value_type );
		$this->assertEquals( 'FALLBACK2', $post_meta->default );
		$this->assertEquals( true, $post_meta->single );
	}

	/** @testdox Gracefully returns null if meta not found. */
	public function test_returns_null_if_post_meta_not_found() {
		$inspector = Meta_Data_Inspector::initialise();
		$post_meta = $inspector->find_post_meta( 'post', 'FAKE' );
		$this->assertNull( $post_meta );
	}

	/** @testdox Can find all meta keys for multiple post types. */
	public function test_find_all_for_post_type(): void {
		$inspector = Meta_Data_Inspector::initialise();
		$post_meta = $inspector->for_post_types( 'post', 'page' );
		$this->assertCount( 3, $post_meta );

		$keys = array( 'post_meta_1', 'post_meta_2', 'page_meta_1' );
		foreach ( $post_meta as $key => $value ) {
			$this->assertTrue( in_array( $value->meta_key, $keys, true ) );
		}

		$this->assertCount( 1, $inspector->for_post_types( 'page' ) );
	}

	/** @testdox Can find term meta based on the key */
	public function test_find_term_meta() {
		$inspector = Meta_Data_Inspector::initialise();
		$term_meta = $inspector->find_term_meta( 'some_tax', 'term1' );
		$this->assertNotNull( $term_meta );
		$this->assertEquals( 'float', $term_meta->value_type );
	}

	/** @testdox Gracefully returns null if term meta not found. */
	public function test_returns_null_if_term_meta_not_found() {
		$inspector = Meta_Data_Inspector::initialise();
		$term_meta = $inspector->find_term_meta( 'term', 'FAKE' );
		$this->assertNull( $term_meta );
	}

	/** @testdox Can find all registered meta for taxonimies */
	public function test_find_all_for_taxonomies(): void {
		$inspector = Meta_Data_Inspector::initialise();
		$term_meta = $inspector->for_taxonomies( 'some_tax', 'other_tax' );
		$this->assertCount( 3, $term_meta );

		$keys = array( 'term1', 'term2', 'term3' );
		foreach ( $term_meta as $key => $value ) {
			$this->assertTrue( in_array( $value->meta_key, $keys, true ) );
		}

		$this->assertCount( 1, $inspector->for_taxonomies( 'other_tax' ) );
	}

	/** @testdox Can find user meta based on the key */
	public function test_find_user_meta() {
		$inspector = Meta_Data_Inspector::initialise();
		$user_meta = $inspector->find_user_meta( 'user1' );
		$this->assertNotNull( $user_meta );

		$user_meta = $inspector->find_user_meta( 'user2' );
		$this->assertNotNull( $user_meta );
	}

	/** @testdox Gracefully returns null if user meta not found. */
	public function test_returns_null_if_user_meta_not_found() {
		$inspector = Meta_Data_Inspector::initialise();
		$user_meta = $inspector->find_user_meta( 'user', 'FAKE' );
		$this->assertNull( $user_meta );
	}

	/** @testdox Can find comment meta based on the key */
	public function test_find_comment_meta() {
		$inspector    = Meta_Data_Inspector::initialise();
		$comment_meta = $inspector->find_comment_meta( 'comment1' );
		$this->assertNotNull( $comment_meta );

		$comment_meta = $inspector->find_comment_meta( 'comment2' );
		$this->assertNotNull( $comment_meta );
	}

	/** @testdox Gracefully returns null if comment meta not found. */
	public function test_returns_null_if_comment_meta_not_found() {
		$inspector    = Meta_Data_Inspector::initialise();
		$comment_meta = $inspector->find_comment_meta( 'comment', 'FAKE' );
		$this->assertNull( $comment_meta );
	}

	/** @testdox Can use filter to do more complex seratches. */
	public function test_can_filter_meta_data() {
		$inspector = Meta_Data_Inspector::initialise();
		$results   = $inspector->filter(
			function( $meta ) {
				return $meta->single;
			}
		);
		$keys      = array( 'post_meta_1', 'post_meta_2', 'page_meta_1' );
		foreach ( $results as $key => $meta_data ) {
			$this->assertTrue( in_array( $meta_data->meta_key, $keys, true ) );
		}
	}
}
