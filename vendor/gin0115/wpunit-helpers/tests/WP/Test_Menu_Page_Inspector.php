<?php

declare(strict_types=1);

/**
 * Tests for Menu Page Inspector.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP;

use Gin0115\WPUnit_Helpers\Output;
use Gin0115\WPUnit_Helpers\WP\Menu_Page_Inspector;
use Gin0115\WPUnit_Helpers\WP\Entities\Menu_Page_Entity;
use Gin0115\WPUnit_Helpers\WP\Entities\Sub_Menu_Page_Entity;
use Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase\User_Factory_Trait;

class Test_Menu_Page_Inspector extends \WP_UnitTestCase {

	use User_Factory_Trait;

	/** @var array<string, array<string, string>> */
	protected const MOCK_PAGE_VALUES = array(
		'parent'  => array(
			'key'   => 'parent_page_stub',
			'title' => 'Parent Page',
		),
		'child1'  => array(
			'key'   => 'child_one_stub',
			'title' => 'Child 1 Page',
		),
		'child2'  => array(
			'key'   => 'child_two_stub',
			'title' => 'Child 2 Page',
		),
		'parent2' => array(
			'key'   => 'parent2_page_stub',
			'title' => 'Parent Page',
		),
		'shared'  => array(
			'key'   => 'shared_page',
			'title' => 'Listed Twice',
		),
	);

	/** @var Menu_Page_Inspector */
	protected $menu_page_inspector;

	public function setUp(): void {
		parent::setUp();

		global $submenu, $menu;
		wp_set_current_user( $this->create_admin_user()->ID );
		set_current_screen( 'dashboard' );

		if ( ! $menu ) {
			$this->register_menu_pages();
		}
		// $this->menu_page_inspector = Menu_Page_Inspector::initialise();
	}

	/**
	 * Registers all test menu pages.
	 *
	 * @return void
	 */
	protected function register_menu_pages(): void {
		// Parent
		add_menu_page(
			self::MOCK_PAGE_VALUES['parent']['title'],
			self::MOCK_PAGE_VALUES['parent']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['parent']['key'],
			function() {
				echo self::MOCK_PAGE_VALUES['parent']['title'];
			}
		);
		// Child 1
		add_submenu_page(
			self::MOCK_PAGE_VALUES['parent']['key'],
			self::MOCK_PAGE_VALUES['child1']['title'],
			self::MOCK_PAGE_VALUES['child1']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['child1']['key'],
			function() {
				echo self::MOCK_PAGE_VALUES['child1']['title'];
			}
		);
		// Child 2
		add_submenu_page(
			self::MOCK_PAGE_VALUES['parent']['key'],
			self::MOCK_PAGE_VALUES['child2']['title'],
			self::MOCK_PAGE_VALUES['child2']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['child2']['key']
		);

		 // Shared
		add_menu_page(
			self::MOCK_PAGE_VALUES['parent2']['title'],
			self::MOCK_PAGE_VALUES['parent2']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['parent2']['key']
		);
		add_submenu_page(
			self::MOCK_PAGE_VALUES['parent2']['key'],
			self::MOCK_PAGE_VALUES['shared']['title'],
			self::MOCK_PAGE_VALUES['shared']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['shared']['key']
		);
		add_submenu_page(
			self::MOCK_PAGE_VALUES['parent']['key'],
			self::MOCK_PAGE_VALUES['shared']['title'],
			self::MOCK_PAGE_VALUES['shared']['title'],
			'manage_options',
			self::MOCK_PAGE_VALUES['shared']['key'],
			function() {
				echo self::MOCK_PAGE_VALUES['shared']['title'];
			}
		);
	}

	/** @testdox Can construct inspector using either constuctor or static */
	public function test_can_construct_inspector(): void {
		// Static
		$this->assertInstanceOf( Menu_Page_Inspector::class, Menu_Page_Inspector::initialise() );

		// Using constructor.
		$inspector = new Menu_Page_Inspector();
		$inspector->set_globals()->do_admin_menu()->set_pages();
		$this->assertInstanceOf( Menu_Page_Inspector::class, $inspector );
	}

	/** @testdox find first either parent or child using find() */
	public function test_can_find_first(): void {

		$inspector = Menu_Page_Inspector::initialise();
		$child     = $inspector->find( self::MOCK_PAGE_VALUES['child2']['key'] );
		$parent    = $inspector->find( self::MOCK_PAGE_VALUES['parent']['key'] );
		$fail      = $inspector->find( 'i_dont_exist' );

		$this->assertInstanceOf( Sub_Menu_Page_Entity::class, $child );
		$this->assertInstanceOf( Menu_Page_Entity::class, $parent );
		$this->assertNull( $fail );
	}

	/** @testdox Can find first parent with matching id/key */
	public function test_can_find_parent(): void {
		$inspector = Menu_Page_Inspector::initialise();
		$parent    = $inspector->find_parent( self::MOCK_PAGE_VALUES['parent']['key'] );
		$this->assertInstanceOf( Menu_Page_Entity::class, $parent );
		$this->assertEquals( self::MOCK_PAGE_VALUES['parent2']['title'], $parent->menu_title );
	}

	/** @testdox Can find first child with matching id/key */
	public function test_can_find_child(): void {
		$inspector = Menu_Page_Inspector::initialise();
		$child     = $inspector->find_child( self::MOCK_PAGE_VALUES['child2']['key'] );
		$this->assertInstanceOf( Sub_Menu_Page_Entity::class, $child );
		$this->assertEquals( self::MOCK_PAGE_VALUES['child2']['title'], $child->menu_title );
	}

	/** @testdox Can find both child pages if shared between 2 parents. */
	public function test_can_find_all_shared_children(): void {
		$inspector = Menu_Page_Inspector::initialise();
		$pages     = $inspector->find_all( self::MOCK_PAGE_VALUES['shared']['key'] );
		$this->assertCount( 2, $pages );
		$this->assertInstanceOf( Sub_Menu_Page_Entity::class, $pages[0] );
		$this->assertInstanceOf( Sub_Menu_Page_Entity::class, $pages[1] );
	}

	/** @testdox Ensure only users with the right permissions can access pages. */
	public function test_can_check_user_access(): void {
		$inspector = Menu_Page_Inspector::initialise();
		$page      = $inspector->find_parent( self::MOCK_PAGE_VALUES['parent2']['key'] );
		$this->assertTrue( $inspector->can_user_access_page( $this->create_admin_user(), $page ) );
		$this->assertFalse( $inspector->can_user_access_page( $this->create_subscriber_user(), $page ) );
	}

	/** @testdox Can get all child pages. */
	public function test_can_get_all_child_pages(): void {
		$inspector   = Menu_Page_Inspector::initialise();
		$child_pages = $inspector->get_all_child_pages();
		foreach ( $child_pages as $child ) {
			$this->assertInstanceOf( Sub_Menu_Page_Entity::class, $child );
		}
	}

	/** @testdox Can render a pages content. */
	public function test_can_render_page(): void {

		// Register some custom pages.
		// These are added (and removed) to allow running do_admin_menu without
		// re registering existing page.
		add_menu_page(
			'some title',
			'some title',
			'manage_options',
			'some_parent',
			function() {
				echo 'some title';
			}
		);

		add_submenu_page(
			'some_parent',
			'some title',
			'some title',
			'manage_options',
			'some_mock_key_child',
			function() {
				echo 'some title';
			}
		);

		// Build, re process and get parent page to render buffer
		$inspector = Menu_Page_Inspector::initialise();
		$inspector->do_admin_menu( true );
		$page = $inspector->find( 'some_parent' );

		$ouput = Output::buffer(
			function() use ( $page, $inspector ) {
				$inspector->render_page( $page );
			}
		);

		// Clean up tenp pages.
		\remove_submenu_page( 'some_parent', 'some_mock_key_child' );
		\remove_menu_page( 'some_parent' );

		$this->assertStringContainsString( 'some title', $ouput );

	}
}
