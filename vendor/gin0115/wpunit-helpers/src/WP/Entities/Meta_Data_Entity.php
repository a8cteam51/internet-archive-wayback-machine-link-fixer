<?php

/**
 * Meta Data Entity
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\Entities;

class Meta_Data_Entity {
	/**
	 * @var string
	 */
	public $meta_type;

	/**
	 * @var string
	 */
	public $sub_type = null;

	/**
	 * @var string
	 */
	public $meta_key;

	/**
	 * @var string
	 */
	public $value_type;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var bool
	 */
	public $single;

	/**
	 * @var callable|null
	 */
	public $sanitize_callback;

	/**
	 * @var callable|null
	 */
	public $auth_callback;

	/**
	 * @var false|array<string, mixed>
	 */
	public $show_in_rest;

	/**
	 * @var string
	 */
	public $default;
}
