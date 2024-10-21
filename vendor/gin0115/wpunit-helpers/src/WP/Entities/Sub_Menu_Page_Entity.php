<?php

/**
 * Sub Menu Page (child) Entity
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\Entities;

use Gin0115\WPUnit_Helpers\WP\Entities\Menu_Page_Interface;

class Sub_Menu_Page_Entity implements Menu_Page_Interface {
	/**
	 * The subpages page title
	 *
	 * @var string
	 */
	public $page_title;
	/**
	 * Menu title
	 *
	 * @var string
	 */
	public $menu_title;
	/**
	 * Which user permissions are required
	 *
	 * @var string
	 */
	public $permission;
	/**
	 * Menu slug
	 *
	 * @var string
	 */
	public $menu_slug;
	/**
	 * Parent pages slug.
	 *
	 * @var string
	 */
	public $parent_slug;

	/**
	 * The subpages URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Page postiton.
	 *
	 * @var float
	 */
	public $position;
}
