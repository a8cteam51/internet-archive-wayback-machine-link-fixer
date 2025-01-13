<?php

declare(strict_types=1);

/**
 * Tests for Meta Box helper class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\Output;
use Gin0115\WPUnit_Helpers\Objects;
use Gin0115\WPUnit_Helpers\WP\Meta_Box_Inspector;
use Gin0115\WPUnit_Helpers\WP\Entities\Meta_Box_Entity;

class Test_Meta_Box_Inspector extends \WP_UnitTestCase {

	/** @var Meta_Box_Inspector */
	protected $meta_box_inpsector;

	public function setUp(): void {
		parent::setUp();
		$this->register_test_meta_boxes();
		$this->meta_box_inpsector = new Meta_Box_Inspector;
		$this->meta_box_inpsector->maybe_register();
	}

	/** Registers all test metaboxes */
	protected function register_test_meta_boxes(): void {
		\add_action(
			'add_meta_boxes',
			function() {
				add_meta_box( 'box_1', 'Box 1', array( $this, 'mock_meta_box_callback' ), array( 'post' ), 'normal', 'high', array( 'key1' => 'value1' ) );
				add_meta_box( 'box_2', 'Box 2', array( $this, 'mock_meta_box_callback' ), array( 'post', 'page' ), 'normal', 'high', array( 'key2' => 'value2' ) );
				add_meta_box( 'box_3', 'Box 3', array( $this, 'mock_meta_box_callback' ), array( 'page' ), 'side', 'low', array( 'key3' => 'value3' ) );
				add_meta_box( 'box_4', 'Box 4', array( $this, 'mock_meta_box_callback' ), array( 'post' ), 'normal', 'high', array( 'key4' => 'value4' ) );
				add_meta_box( 'box_5', 'Box 5', array( $this, 'mock_meta_box_callback' ), array( 'post' ), 'side', 'low', array( 'key5' => 'value5' ) );
				add_meta_box( 'box_6', 'Box 6', array( $this, 'mock_meta_box_callback' ), array( 'post' ), 'advanced', 'core', array( 'key6' => 'value6' ) );
			}
		);
	}

	/** Simple mock callback for rendering a meta box view. */
	public function mock_meta_box_callback( \WP_Post $post, array $args = array() ): void {
		print $post->post_title;
		foreach ( $args as $key => $value ) {
			Output::println( "{$key} -> {$value}" );
		}
	}

	/** @testdox Can initialise the inspector with a static call. */
	public function test_static_initialiser(): void {
		$inspector = Meta_Box_Inspector::initialise();
		$this->assertNotEmpty( $inspector->meta_boxes );
	}

	/** @testdox Check that the meta box global can be recalled.*/
	public function test_can_get_globally_registered_meta_boxes(): void {
		$this->assertIsArray( $this->meta_box_inpsector->from_global() );
	}

	/** @testdox Test can populate the helper with hydrated meta boxes */
	public function test_can_set_hydrated_meta_boxes() {
		$this->meta_box_inpsector->set_meta_boxes();
		$this->assertNotEmpty( Objects::get_property( $this->meta_box_inpsector, 'meta_boxes' ) );
	}

	/** @testdox Check that a find() returns meta boxes based on its id/key */
	public function test_can_find_meta_boxes_by_id(): void {
		$this->meta_box_inpsector->set_meta_boxes();

		$cases = array(
			'box_1' => 'Box 1',
			'box_2' => 'Box 2',
			'box_3' => 'Box 3',
			'box_4' => 'Box 4',
			'box_5' => 'Box 5',
			'box_6' => 'Box 6',
		);

		foreach ( $cases as $key => $title ) {
			$this->assertSame(
				$title,
				$this->meta_box_inpsector->find( $key )->title
			);
		}
	}

	/** @testdox Check that metaboxes can be found based on their post type. */
	public function test_can_get_meta_boxes_based_on_post_type(): void {
		$this->meta_box_inpsector->set_meta_boxes();

		$this->assertCount( 5, $this->meta_box_inpsector->for_post_types( 'post' ) );
		$this->assertCount( 2, $this->meta_box_inpsector->for_post_types( 'page' ) );
		// Finds 7 as each post_type declared is classed as a unique meta box.
		$this->assertCount( 7, $this->meta_box_inpsector->for_post_types( 'post', 'page' ) );
	}

	/** @testdox Can filter the meta boxes based on any value */
	public function test_can_filter_meta_boxes(): void {
		$this->meta_box_inpsector->set_meta_boxes();

		$filtered = $this->meta_box_inpsector->filter(
			function( Meta_Box_Entity $box ): bool {
				return $box->position === 'side';
			}
		);

		$this->assertCount( 2, $filtered );
	}

	/** @testdox Can render the view of a metabox based on the post post passed. */
	public function test_can_invoke_view(): void {
		$this->meta_box_inpsector->set_meta_boxes();

		$box  = $this->meta_box_inpsector->find( 'box_3' );
		$post = \get_post( $this->factory->post->create( array( 'post_title' => 'invoked' ) ) );

		$output = Output::buffer(
			function() use ( $box, $post ) {
				$this->meta_box_inpsector->render_meta_box( $box, $post );
			}
		);

		$this->assertStringContainsString( 'invoked', $output );
		$this->assertStringContainsString( 'key3', $output );
	}
}
