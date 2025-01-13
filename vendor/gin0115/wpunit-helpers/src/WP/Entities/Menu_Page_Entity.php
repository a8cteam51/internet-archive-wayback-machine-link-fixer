<?php


/**
 * Menu Page (parent) Entity
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\Entities;

use Gin0115\WPUnit_Helpers\WP\Entities\Menu_Page_Interface;

class Menu_Page_Entity implements Menu_Page_Interface {

	/**
	 * The pages displayed title
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * The menu title
	 *
	 * @var string
	 */
	public $menu_title;

	/**
	 * Min permissions needed to access
	 *
	 * @var string
	 */
	public $permission;

	/**
	 * The pages menu slug
	 *
	 * @var string
	 */
	public $menu_slug;

	/**
	 * The hook name
	 *
	 * @var string
	 */
	public $hook_name;

	/**
	 * Icon to display
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Page postiton.
	 *
	 * @var float
	 */
	public $position;

	/**
	 * The pages URL
	 * This sometimes doesnt work as expect, with some plugins which create custom rules
	 * such as WooCommerce.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Array of children pages
	 *
	 * @var array<Sub_Menu_Page_Entity>
	 */
	public $children = array();
}
