<?php

/**
 * Helper class for testing meta boxes.
 * Allows for the verifcation and invoking of meta_boxes.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare(strict_types=1);

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\Utils;
use Gin0115\WPUnit_Helpers\WP\Entities\Meta_Data_Entity;
use PinkCrab\FunctionConstructors\{
	Arrays as Arr,
	Comparisons as C,
	GeneralFunctions as F
};

class Meta_Data_Inspector {

	/**
	 * Registered meta data, mapped as Meta_Data_Entity Models.
	 *
	 * @var array<Meta_Data_Entity>
	 */
	public $registered_meta_data = array();

	/**
	 * The WP global for all meta data.
	 * type => [
	 *  sub_type => [
	 *    meta_key => [meta details]
	 *  ]
	 * ]
	 *
	 * @var array<string, mixed[]>|null
	 */
	protected $wp_meta_keys = null;

	/**
	 * Static constructor
	 *
	 * @return self
	 */
	public static function initialise(): self {
		$instance = new self();
		$instance->set_registered_meta_data();
		return $instance;
	}

	/**
	 * Sets the intneral registered meta data array.
	 * Will not reset, if already populated, unless bool TRUE passed
	 * into the constructor.
	 */
	public function set_registered_meta_data( bool $force_reset = false ): self {
		if (
			count( $this->registered_meta_data ) === 0
			|| $force_reset === true
		) {
			global $wp_meta_keys;
			$this->wp_meta_keys = $wp_meta_keys;

			// Map the globals.
			$this->map_meta_by_type();
		}

		return $this;
	}

	/**
	 * Maps the registered meta data to entities from its
	 * primary type
	 *
	 * LEVEL 1
	 *
	 * @return self
	 */
	protected function map_meta_by_type(): self {
		$this->registered_meta_data = Arr\flattenByN( 1 )(
			Utils::iterable_map_with(
				function ( $type, $meta_data ) {
					return $this->map_meta_by_subtype( $type, $meta_data );
				},
				$this->wp_meta_keys ?? array()
			)
		);
		return $this;
	}

	/**
	 * Maps all meta based on the sub types.
	 *
	 * LEVEL 2
	 *
	 * @param string $type
	 * @param array<string, mixed[]> $subtypes
	 * @return array<Meta_Data_Entity>
	 */
	protected function map_meta_by_subtype( string $type, array $subtypes ): array {
		return Arr\flattenByN( 1 )(
			Utils::iterable_map_with(
				function ( string $subtype, array $meta_data, string $type ): array {
					return $this->map_meta_by_key( $type, $subtype, $meta_data );
				},
				$subtypes,
				$type
			)
		);
	}

	/**
	 * Maps all registered meta to entities based on an array
	 * of meta keys => meta data.
	 *
	 * LEVEL 3
	 *
	 * @param string $type
	 * @param string $subtype
	 * @param array<string, mixed[]> $meta
	 * @return array<Meta_Data_Entity>
	 */
	protected function map_meta_by_key( string $type, string $subtype, array $meta ): array {
		return Arr\flattenByN( 1 )(
			Utils::iterable_map_with(
				function ( string $meta_key, array $meta_details, string $type, string $subtype ): Meta_Data_Entity {
					$entity                    = new Meta_Data_Entity();
					$entity->meta_type         = $type;
					$entity->sub_type          = strlen( $subtype ) === 0 ? '_' : $subtype;
					$entity->meta_key          = $meta_key;
					$entity->value_type        = $meta_details['type'];
					$entity->description       = $meta_details['description'];
					$entity->single            = $meta_details['single'];
					$entity->sanitize_callback = $meta_details['sanitize_callback'];
					$entity->auth_callback     = $meta_details['auth_callback'];
					$entity->default           = ! empty( $meta_details['default'] ) ? $meta_details['default'] : '';
					$entity->show_in_rest      = $meta_details['show_in_rest'];
					return $entity;
				},
				$meta,
				$type,
				$subtype
			)
		);
	}

	/**
	 * Returns the first matching meta key and post type found
	 *
	 * @param string $post_type
	 * @param string $meta_key
	 * @return Meta_Data_Entity|null
	 */
	public function find_post_meta( string $post_type, string $meta_key ): ?Meta_Data_Entity {
		$results = Arr\filterAnd(
			F\propertyEquals( 'meta_type', 'post' ),
			F\propertyEquals( 'sub_type', $post_type ),
			F\propertyEquals( 'meta_key', $meta_key )
		)( $this->registered_meta_data );
		return count( $results ) === 0
			? null
			: reset( $results );
	}

	/**
	 * Returns all meta fields for the defined post types.
	 *
	 * @param string ...$post_types
	 * @return array<Meta_Data_Entity>
	 */
	public function for_post_types( string ...$post_types ): array {
		return F\compose(
			Arr\filterAnd(
				F\propertyEquals( 'meta_type', 'post' ),
				F\compose( F\getProperty( 'sub_type' ), C\isEqualIn( $post_types ) )
			),
			'array_values'
		)( $this->registered_meta_data );
	}

	/**
	 * Finds the first matcing, registered term meta
	 * based on the key and taxonomy passed.
	 *
	 * @param string $taxonomy
	 * @param string $term_meta
	 * @return Meta_Data_Entity|null
	 */
	public function find_term_meta( string $taxonomy, string $term_meta ): ?Meta_Data_Entity {
		$results = Arr\filterAnd(
			F\propertyEquals( 'meta_type', 'term' ),
			F\propertyEquals( 'sub_type', $taxonomy ),
			F\propertyEquals( 'meta_key', $term_meta )
		)( $this->registered_meta_data );
		return count( $results ) === 0
			? null
			: reset( $results );
	}

	/**
	 * Returns back all registered for the defined taxonomies.
	 *
	 * @param string ...$taxonomies
	 * @return array<Meta_Data_Entity>
	 */
	public function for_taxonomies( string ...$taxonomies ): array {
		return array_values(
			Arr\filterAnd(
				F\propertyEquals( 'meta_type', 'term' ),
				F\compose( F\getProperty( 'sub_type' ), C\isEqualIn( $taxonomies ) )
			)( $this->registered_meta_data )
		);
	}

	/**
	 * Returns the first matching user meta found with the
	 * defined key.
	 *
	 * @param string $meta_key
	 * @return Meta_Data_Entity|null
	 */
	public function find_user_meta( string $meta_key ): ?Meta_Data_Entity {
		$results = Arr\filterAnd(
			F\propertyEquals( 'meta_type', 'user' ),
			F\propertyEquals( 'meta_key', $meta_key )
		)( $this->registered_meta_data );
		return count( $results ) === 0
			? null
			: reset( $results );
	}

	/**
	 * Returns the first matching comment meta found with the
	 * defined key.
	 *
	 * @param string $meta_key
	 * @return Meta_Data_Entity|null
	 */
	public function find_comment_meta( string $meta_key ): ?Meta_Data_Entity {
		$results = Arr\filterAnd(
			F\propertyEquals( 'meta_type', 'comment' ),
			F\propertyEquals( 'meta_key', $meta_key )
		)( $this->registered_meta_data );
		return count( $results ) === 0
			? null
			: reset( $results );
	}

	/**
	 * Allows the filtering of the registered meta, based
	 * on the passed filter fucntion.
	 *
	 * @param callable(Meta_Data_Entity): bool $filter
	 * @return array<Meta_Data_Entity>
	 */
	public function filter( callable $filter ): array {
		return array_filter( $this->registered_meta_data, $filter );
	}
}
