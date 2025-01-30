<?php

/**
 * Helper class for testing meta boxes.
 * Allows for the verifcation and invoking of meta_boxes.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\Utils;
use Gin0115\WPUnit_Helpers\WP\Entities\Meta_Box_Entity;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Comparisons as C;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;

class Meta_Box_Inspector {

	/**
	 * Internal Collection of meta_boxes
	 *
	 * @var array<Meta_Box_Entity>
	 */
	public $meta_boxes = array();

	/**
	 * Self contained, initliaser
	 *
	 * @return Meta_Box_Inspector
	 */
	public static function initialise(): Meta_Box_Inspector {
		$instance = new self();
		$instance->maybe_register()->set_meta_boxes();
		return $instance;
	}

	/**
	 * Returns all the current meta_boxes, or null if not set.
	 * Fire add_meta_boxes to add any waiting.
	 *
	 * @return array<string, mixed[]>|null
	 */
	public function from_global(): ?array {
		global $wp_meta_boxes;
		return $wp_meta_boxes;
	}

	/**
	 * Registers meta_boxes, if they have not already been set.
	 *
	 * @return self
	 */
	public function maybe_register(): self {
		if ( $this->from_global() === null ) {
			\do_action( 'add_meta_boxes' );
		}
		return $this;
	}

	/**
	 * Starts the mapping process.
	 *
	 * @return self
	 */
	public function set_meta_boxes(): self {
		foreach ( $this->from_global() ?? array() as $post_type => $meta_boxes ) {
			$this->meta_boxes = array_merge(
				$this->meta_boxes,
				$this->map_position( $post_type, $meta_boxes )
			);
		}
		return $this;
	}

	/**
	 * Maps all meta boxes based on postition (2nd Level)
	 *
	 * @param string $post_type
	 * @param array<string, mixed[]> $meta_boxes
	 * @return array<Meta_Box_Entity>
	 */
	protected function map_position( string $post_type, array $meta_boxes ): array {
		$results = array();
		foreach ( $meta_boxes as $position => $meta_boxes ) {
			$results = array_merge(
				$results,
				$this->map_priority( $post_type, $position, $meta_boxes )
			);
		}
		return $results;
	}

	/**
	 * Maps all based on priority (3rd Level)
	 *
	 * @param string $post_type
	 * @param string $position
	 * @param array<string, mixed[]> $meta_boxes
	 * @return array<Meta_Box_Entity>
	 */
	protected function map_priority( string $post_type, string $position, array $meta_boxes ): array {
		$results = array();
		foreach ( $meta_boxes as $priority => $named_meta_boxes ) {
			$results = array_merge(
				$results,
				$this->map_named_meta_boxes( $named_meta_boxes, $post_type, $position, $priority )
			);
		}
		return $results;
	}

	/**
	 * Maps all based on meta box names. (4th Level)
	 *
	 * @param string $post_type
	 * @param string $position
	 * @param string $priority
	 * @param array<string, mixed[]|null> $meta_boxes
	 * @return array<Meta_Box_Entity>
	 */
	protected function map_named_meta_boxes(
		array $meta_boxes,
		string $post_type,
		string $position,
		string $priority
	): array {
		return Utils::iterable_map_with(
			function( $name, $meta_box_details, $post_type, $position, $priority ): Meta_Box_Entity {
				$meta_box            = new Meta_Box_Entity();
				$meta_box->post_type = $post_type;
				$meta_box->position  = $position;
				$meta_box->priority  = $priority;
				$meta_box->name      = $name;

				if ( \is_array( $meta_box_details ) ) {
					$meta_box->isset    = true;
					$meta_box->id       = $meta_box_details['id'];
					$meta_box->title    = $meta_box_details['title'];
					$meta_box->callback = $meta_box_details['callback'];
					$meta_box->args     = $meta_box_details['args'];
				}
				return $meta_box;
			},
			$meta_boxes,
			$post_type,
			$position,
			$priority
		);
	}

	/**
	 * Returns all meta_boxes for a multiple post types.
	 *
	 * @param string ...$post_type
	 * @return array<Meta_Box_Entity>
	 */
	public function for_post_types( string ...$post_type ): array {
		return array_filter(
			$this->meta_boxes,
			F\compose( F\getProperty( 'post_type' ), C\isEqualIn( $post_type ) )
		);
	}

	/**
	 * Attempts to find a meta_box based on id.
	 * Returns first instance found.
	 *
	 * @param string $id
	 * @return Meta_Box_Entity|null
	 */
	public function find( string $id ): ?Meta_Box_Entity {
		return Arr\filterFirst( F\propertyEquals( 'id', $id ) )( $this->meta_boxes );
	}

	/**
	 * Allows the filtering of meta boxes.
	 *
	 * @param callable $filter
	 * @return array<Meta_Box_Entity>
	 */
	public function filter( callable $filter ): array {
		return array_filter( $this->meta_boxes, $filter );
	}

	/**
	 * Renders a meta_box based on a post type passed.
	 * Prints the contents!
	 *
	 * @param Meta_Box_Entity $meta_box
	 * @param \WP_Post $post
	 * @return void
	 */
	public function render_meta_box( Meta_Box_Entity $meta_box, \WP_Post $post ): void {
		$callback = $meta_box->callback;
		$callback( $post, $meta_box->args );
	}

}
