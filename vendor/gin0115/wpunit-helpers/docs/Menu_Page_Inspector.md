# Menu_Page_Inspector

The Menu Page Inspector gives access to a combined, queryable wrapper around $menu & $submenu globals. Allows for quick and easy testing that all added menu and sub menu pages registered. You can search based on the defined slug, against both parent, sub page types. 

Also allows for testing that users can access the page and rendering a pages content testing.

## Setup

Set globals sets the global state to the Inspector. If they have not yet been populated, they will be set. Calling do_admin_menu will run the action to populate the globals. Also a static constructor, that does all the setup too. This will allow you to do all as one liner. 

``` php
$inspector = new Menu_Page_Inspector;
$inspector->set_globals()->do_admin_menu()->set_pages(); 
//or 
$inspector = Menu_Page_Inspector::initialise();
```

**You can repopulate the internal state of the inspector from the globals by calling**
```php
$inspetor = $inspector->set_globals(true)->do_admin_menu(true)->set_pages();
```
*Both set_globals and do_admin_menu pass false as default, either can forced by passing true*


### find(string $menu_slug): ? Menu_Page_Interface

This allows for searching against both parent and child page types. This will return the first result it finds. Based on the page type found, will return either **Menu_Page_Entity** or **Sub_Menu_Page_Entity**

``` php
$main_page = Menu_Page_Inspector::initialise()->find('ache_settings_page');
$this->assertInstanceOf(Menu_Page_Interface::class, $menu_page);
$this->assertEquals('Achme Settings', $menu_page->menu_title);
```

### find_parent(string $menu_slug): ? Menu_Page_Entity

Checks all parent pages for a matching menu slug. Returns null if it doesnt exist, allows for the same test cases above. Just more strict on the return type.

``` php
$main_page = Menu_Page_Inspector::initialise()->find_parent('ache_settings_page');
$this->assertInstanceOf(Menu_Page_Entity::class, $menu_page);
$this->assertEquals('Achme Settings', $menu_page->menu_title);
```

### find_child(string $menu_slug): ? Sub_Menu_Page_Entity

Searches only child pages for a matching menu slug.

``` php
$main_page = Menu_Page_Inspector::initialise()->find_child('ache_settings_page');
$this->assertInstanceOf(Sub_Menu_Page_Entity::class, $menu_page);
$this->assertEquals('Achme Settings', $menu_page->menu_title);
```

### find_all(string $menu_slug): array<Menu_Page_Interface>

Returns an array of all matching parent and child pages with the same menu slug.

### can_user_access_page( \WP_User $user, Menu_Page_Interface $page ): bool

You can test user access rights to pages by passing a user isntances. Uses the user roles, allowing for simple mocking of users.

``` php
$inspector = Menu_Page_Inspector::initialise();
// Find page
$main_page = $inspector->find('ache_settings_page');
// Check an admin can access and not a regular subscriber.
$this->assertTrue($inspector->can_user_access_page($admin_user, $menu_page));
$this->assertFalse($inspector->can_user_access_page($subscriber_user, $menu_page));
```

### render_page( Menu_Page_Interface $page ): void

You can use this to render the view of any menu page. This allows for intergration and functional tests withing phpunit. 

Will print the pages contents, so either use ` `  ` $this->expectOutput* `  `  ` or the `  `  ` Output::buffer() `  ` ` as provided in this libray.

```php 
// Build inspector and find page.
$inspector = Menu_Page_Inspector::initialise(); 
$page = $inspector->find('page_slug'); 

$output = Output::buffer(function() use ($inspector, $page){
    $inspector->render($page);
}); 

$this->assertStringContainsString('id=\'page_foo_nonce\'', $output); 
$this->assertStringContainsString($page->page_title, $output); 

```

## Methods & Properites

### Menu_Page_Inspector::class

Main class, *has some internal methods not listed here*

```php	
/**
 * All current admin pages
 *
 * @var Menu_Page_Entity
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
 * @var array<string, array|null>
 */
protected $globals = array(
    'menu'    => null,
    'submenu' => null,
);    

/**
 * Creates an instance, calls admin_meny action, sets globals
 * and popules the admin page array.
 *
 * @return self
 */
public static function initialise(): self

/**
 * Sets the globals for menu and submenu.
 *
 * @param bool $force Force a reset of internal array of $menu & $submenu globals
 * @return self
 */
public function set_globals( bool $force = false ): self

/**
 * Runs the admin_menu action if its not been called.
 *
 * @param bool $force If true, will rerun do_action( 'admin_menu' );
 * @return self
 */
public function do_admin_menu( bool $force = false ): self

/**
 * Sets the current state of the menu and submenus globals to
 * the inner array.
 *
 * @return self
 */
public function set_pages(): self

/**
 * Extracts all child pages and flattens them into a single array.
 *
 * @return array<Sub_Menu_Page_Entity>
 */
public function get_all_child_pages(): array

    /**
 * Finds the first child or parent page.
 *
 * @param string $menu_slug
 * @return Menu_Page_Interface|null
 */
public function find( string $menu_slug ): ?Menu_Page_Interface

/**
 * Finds all children with the matching slug.
 *
 * @param string $menu_slug
 * @return array<Menu_Page_Interface>
 */
public function find_all( string $menu_slug ): array

/**
 * Attempts to find a parent page based on its menu slug.
 *
 * @param string $menu_slug
 * @return Menu_Page_Entity|null
 */
public function find_parent( string $menu_slug ): ?Menu_Page_Entity

/**
 * Attempts to find the first child page with a matching slug.
 *
 * @param string $menu_slug
 * @return Sub_Menu_Page_Entity|null
 */
public function find_child( string $menu_slug ): ?Sub_Menu_Page_Entity

/**
 * Checks if a user is allowed to access the page.
 *
 * @param \WP_User $user
 * @param Menu_Page_Interface $page
 * @return bool
 */
public function can_user_access_page( \WP_User $user, Menu_Page_Interface $page ): bool

/**
 * Renders an admin page based on a Page_Entity
 *
 * Ensure you set all POST/GET states before calling.
 *
 * Please note this will not work for menu pages which have been added as links
 * to edit.php, plugin.php etc, only with pages registered via add_menu_page() or
 * any of the sub pages.
 *
 * @param Menu_Page_Interface $page
 * @return void
 */
public function render_page( Menu_Page_Interface $page ): void
```
### Menu_Page_Entity

Parent menu page model
```php
/** @var string	*/
public $page_title;

/** @var string	*/
public $menu_title;

/** @var string	*/
public $permission;

/** @var string	*/
public $menu_slug;

/** @var string	*/
public $hook_name;

/** @var string	*/
public $icon;

/** @var float */
public $position;

/** @var string	*/
public $url;

/** @var array<Sub_Menu_Page_Entity> */
public $children = array();
```

### Sub_Menu_Page_Entity

Parent menu page model
```php
/** @var string	*/
public $page_title;

/** @var string	*/
public $menu_title;

/** @var string	*/
public $permission;

/** @var string	*/
public $menu_slug;

/** @var string	*/
public $parent_slug;

/** @var float */
public $position;

/** @var string	*/
public $url;
```

Please note both **Menu_Page_Entity** & **Sub_Menu_Page_Entity** implement the **Menu_Page_Interface**, this interface has no methods and is to act as union between pages.
