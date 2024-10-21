<?php

/**
 * Helper class for validating Menu Pages and Sub Pages
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\Utils;
use Gin0115\WPUnit_Helpers\WP\Entities\Menu_Page_Interface;
use Gin0115\WPUnit_Helpers\WP\Entities\Sub_Menu_Page_Entity;
use Gin0115\WPUnit_Helpers\WP\Entities\Menu_Page_Entity;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;

class Menu_Page_Inspector {

	/**
	 * All current admin pages
	 *
	 * @var array<Menu_Page_Entity>
	 */
	public $admin_pages = array();

	/**
	 * Current global states
	 *
	 * $submenu item array structure.
	 * [0] => Menu Title
	 * [1] => Permission
	 * [2] => Key/Slug
	 * [3] => Page Title
	 *
	 * $menu item array structure.
	 * [0] => Menu Title
	 * [1] => Permission
	 * [2] => Key/Slug
	 * [3] => Page Title
	 * [4] => Menu Class
	 * [5] => Hookname
	 * [6] => Icon
	 *
	 * @var array<string, mixed[]|null>
	 */
	protected $globals = array(
		'menu'    => null,
		'submenu' => null,
	);

	/**
	 * Creates an instance, calls admin_menu action, sets globals
	 * and populates the admin page array.
	 *
	 * @param bool $force If set to true, will reset and rebuild the internal state.
	 * @return self
	 */
	public static function initialise( bool $force = false ): self {
		$instance = new self();

		if ( $force ) {
			$instance->reset_globals();
		}

		$instance->do_admin_menu( $force );
		$instance->set_globals( $force );
		$instance->set_pages();

		return $instance;
	}

	/**
	 * Sets the globals for menu and submenu.
	 *
	 * @param bool $force Force a reset of internal array of $menu & $submenu globals
	 * @return self
	 */
	public function set_globals( bool $force = false ): self {
		if ( $this->globals['menu'] === null
		|| $this->globals['submenu'] === null
		|| $force ) {
			global $menu, $submenu;
			$this->globals['menu']    = $menu;
			$this->globals['submenu'] = $submenu;
		}
		return $this;
	}

	/**
	 * Resets the menu globals and internal state (to null)
	 *
	 * @return self
	 */
	public function reset_globals(): self {
		global $menu, $submenu;
		$menu                     = null; //phpcs:ignore
		$submenu                  = null; //phpcs:ignore
		$this->globals['menu']    = null;
		$this->globals['submenu'] = null;
		return $this;
	}

	/**
	 * Runs the admin_menu action if its not been called.
	 *
	 * If being used on a website, do not call this if in wp-admin as will
	 * cause an infinite loop.
	 *
	 * @param bool $force If true, will rerun do_action( 'admin_menu' );
	 * @return self
	 */
	public function do_admin_menu( bool $force = false ): self {
		if ( ! \did_action( 'admin_menu' ) || $force ) {
			\do_action( 'admin_menu' );
		}
		return $this;
	}

	/**
	 * Returns all the menu items with seperators removed.
	 *
	 * @return array<int, array<string>>
	 */
	protected function menu_items_without_separators(): array {
		return array_filter(
			$this->globals['menu'] ?? array(),
			function( array $menu_item ): bool {
				return ! Str\contains( 'separator' )( $menu_item[2] )
				|| $menu_item[4] !== 'wp-menu-separator';
			},
			\ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Sets the current state of the menu and submenus globals to
	 * the inner array.
	 *
	 * @return self
	 */
	public function set_pages(): self {
		foreach ( $this->menu_items_without_separators()
			as $position => $menu_item ) {
			$this->admin_pages[ $menu_item[2] ] =
				$this->hydrate_parent_menu_page_entity(
					array(
						'parent'   => $menu_item,
						'position' => $position,
						'key'      => $menu_item[2],
						'children' => $this->get_sub_pages( $menu_item ),
					)
				);
		}
		return $this;
	}

	/**
	 * Gets all the sub menu pages, pased on the passed parent
	 * array (from gloabl $menu).
	 *
	 * @param array<int, string> $parent
	 * @return array<string, string>
	 */
	protected function get_sub_pages( array $parent ): array {
		if ( \is_null( $this->globals['submenu'] )
		|| ! \array_key_exists( $parent[2], $this->globals['submenu'] ) ) {
			return array();
		}

		return $this->globals['submenu'][ $parent[2] ];
	}

	/**
	 * Hydrates the menu page items to models.
	 *
	 * @param array<string, mixed> $menu_item
	 * @return Menu_Page_Entity
	 */
	protected function hydrate_parent_menu_page_entity( array $menu_item ): Menu_Page_Entity {
		$page             = new Menu_Page_Entity();
		$page->menu_title = $menu_item['parent'][0];
		$page->permission = $menu_item['parent'][1];
		$page->menu_slug  = $menu_item['parent'][2];
		$page->page_title = $menu_item['parent'][3];
		$page->hook_name  = $menu_item['parent'][5];
		$page->icon       = $menu_item['parent'][6];
		$page->url        = \menu_page_url( $menu_item['parent'][2], false );
		$page->children   = $this->hydrate_child_menu_page_entity(
			$menu_item['children'],
			$page->menu_slug
		);
		$page->position   = (float) $menu_item['position'];
		return $page;
	}

	/**
	 * Hydrates a sub page model
	 *
	 * @param array<int, string> $children
	 * @param string $parent_key
	 * @return array<Sub_Menu_Page_Entity>
	 */
	protected function hydrate_child_menu_page_entity( array $children, string $parent_key ): array {
		return Utils::iterable_map_with(
			function( $key, $child, $parent_key ) {
				$page              = new Sub_Menu_Page_Entity();
				$page->menu_slug   = $child[2];
				$page->parent_slug = $parent_key;
				$page->menu_title  = $child[0];
				$page->permission  = $child[1];
				$page->page_title  = $child[3];
				$page->url         = \menu_page_url( $child[2], false );
				$page->position    = (float) $key;
				return $page;
			},
			$children,
			$parent_key
		);
	}

	/**
	 * Extracts all child pages and flattens them into a single array.
	 *
	 * @return array<Sub_Menu_Page_Entity>
	 */
	public function get_all_child_pages(): array {
		$children = array_map( F\pluckProperty( 'children' ), $this->admin_pages );
		return Arr\flattenByN( 1 )( $children );
	}

	/**
	 * Finds the first page or group
	 *
	 * @param string $menu_slug
	 * @return Menu_Page_Interface|null
	 */
	public function find( string $menu_slug ): ?Menu_Page_Interface {
		return Arr\filterFirst(
			F\propertyEquals( 'menu_slug', $menu_slug )
		)( array_merge( $this->admin_pages, $this->get_all_child_pages() ) );
	}

	/**
	 * Finds all children with the matching slug.
	 *
	 * @param string $menu_slug
	 * @return array<Menu_Page_Interface>
	 */
	public function find_all( string $menu_slug ): array {
		return array_values(
			Arr\Filter(
				F\propertyEquals( 'menu_slug', $menu_slug )
			)( array_merge( $this->admin_pages, $this->get_all_child_pages() ) )
		);
	}

	/**
	 * Attempts to find a parent page based on its menu slug.
	 *
	 * @param string $menu_slug
	 * @return Menu_Page_Entity|null
	 */
	public function find_parent( string $menu_slug ): ?Menu_Page_Entity {
		return \array_key_exists( $menu_slug, $this->admin_pages )
			? $this->admin_pages[ $menu_slug ] : null;
	}

	/**
	 * Attempts to find a parent page based on its menu slug.
	 *
	 * @param string $menu_slug
	 * @return Menu_Page_Entity|null
	 */
	public function find_group( string $menu_slug ): ?Menu_Page_Entity {
		return \array_key_exists( $menu_slug, $this->admin_pages )
			? $this->admin_pages[ $menu_slug ] : null;
	}

	/**
	 * Attempts to find the first child page with a matching slug.
	 *
	 * @param string $menu_slug
	 * @return Sub_Menu_Page_Entity|null
	 */
	public function find_child( string $menu_slug ): ?Sub_Menu_Page_Entity {
		return Arr\filterFirst( F\propertyEquals( 'menu_slug', $menu_slug ) )( $this->get_all_child_pages() );
	}


	/**
	 * Checks if a user is allowed to access the page.
	 *
	 * @param \WP_User $user
	 * @param Menu_Page_Entity|Sub_Menu_Page_Entity $page
	 * @return bool
	 */
	public function can_user_access_page( \WP_User $user, Menu_Page_Interface $page ): bool {
		return $user->has_cap( $page->permission );
	}

	/**
	 * Renders an admin page based on a Page_Entity
	 *
	 * Ensure you set all POST/GET states before calling.
	 *
	 * Please note this will not work for menu pages which have been added as links
	 * to edit.php, plugin.php etc, only with pages registered via add_menu_page() or
	 * any of the sub pages.
	 *
	 * @param Menu_Page_Entity|Sub_Menu_Page_Entity $page
	 * @return void
	 */
	public function render_page( Menu_Page_Interface $page ): void {

		$page_hook = is_a( $page, Menu_Page_Entity::class )
			/** @var  Menu_Page_Entity */
			? \get_plugin_page_hookname( $page->menu_slug, '' ) // Parent
			/** @var  Sub_Menu_Page_Entity */
			: \get_plugin_page_hookname( $page->menu_slug, $page->parent_slug );
		do_action( $page_hook, function( $e ) {} );
	}
}
