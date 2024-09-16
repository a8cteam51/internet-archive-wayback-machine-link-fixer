<?php

/**
 * meta box item entity
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\Entities;

class Meta_Box_Entity {

	/**
	 * All post types this meta box is applied to.
	 * @var string
	 */
	public $post_type;

	/**
	 * Position/Context (Side|Normal)
	 * @var string
	 */
	public $position;

	/**
	 * Display priority for sorting.
	 * @var string
	 */
	public $priority;

	/**
	 * Internal WP reference, used as the key in global meta box array.
	 * Should not be used to check compare with a defined meta box key, use $id
	 * @var string
	 */
	public $name;

	/**
	 * Has the callback been registered yet.
	 * Represents false for meta box details in Meta_Box_Inspector global
	 * @var bool
	 */
	public $isset = false;

	/**
	 * Defined meta box id/key, used when registering.
	 * @var string
	 */
	public $id;

	/**
	 * meta box title
	 * @var string
	 */
	public $title;

	/**
	 * Defined callback
	 * @var callable
	 */
	public $callback;

	/**
	 * Defined args
	 * @var array<string, mixed>
	 */
	public $args = array();

}
