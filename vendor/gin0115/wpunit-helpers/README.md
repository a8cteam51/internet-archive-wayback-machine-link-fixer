# WPUnit_Helpers

Collection of helper functions, classes and traits for using WPUnit. 

[![Latest Stable Version](http://poser.pugx.org/gin0115/wpunit-helpers/v)](https://packagist.org/packages/gin0115/wpunit-helpers)
[![Total Downloads](http://poser.pugx.org/gin0115/wpunit-helpers/downloads)](https://packagist.org/packages/gin0115/wpunit-helpers)
[![License](http://poser.pugx.org/gin0115/wpunit-helpers/license)](https://packagist.org/packages/gin0115/wpunit-helpers)
[![PHP Version Require](http://poser.pugx.org/gin0115/wpunit-helpers/require/php)](https://packagist.org/packages/gin0115/wpunit-helpers)
![GitHub contributors](https://img.shields.io/github/contributors/gin0115/WPUnit_Helpers?label=Contributors)
![GitHub issues](https://img.shields.io/github/issues-raw/gin0115/WPUnit_Helpers)
![](https://github.com/gin0115/WPUnit_Helpers/workflows/GitHub_CI/badge.svg " ")
[![codecov](https://codecov.io/gh/gin0115/WPUnit_Helpers/branch/main/graph/badge.svg?token=0IFKfuE5Sf)](https://codecov.io/gh/gin0115/WPUnit_Helpers)
[![Maintainability](https://api.codeclimate.com/v1/badges/5d49d0d2ac54b59c84d3/maintainability)](https://codeclimate.com/github/gin0115/WPUnit_Helpers/maintainability)

## Version

**1.0.8**

## Setup

```bash
$ composer require --dev gin0115/wpunit-helpers
```

## Meta Box Inspector

Check if meta boxes have been registered correctly, check all values and render the view callback.

```php
$box = Meta_Box_Inspector::initialise()->find('my_meta_box_key');
$this->assertInstanceOf(Meta_Box_Entity::class, $box);
$this->assertEquals('My Title', $box->title);
```

**[Read More](docs/Meta_Box_Inspector.md)**

## Menu Page Inspector

Allows for the checking of added pages and sub pages. Can be searched to ensure pages are added as expected and can render the pages content, for intergration style tests. Allows for testing parent and child(sub) pages.

```php
$page = Menu_Page_Inspector::initialise()->find_parent_page('parent_page_slug');
$this->assertInstanceOf(Menu_Page_Entity::class, $page);
$this->assertEquals('My Settings', $page->menu_title);
```

**[Read More](docs/Menu_Page_Inspector.md)**

## Meta Data Inspector

Allows for the checking of registered meta data, for either post, term, user, comment and any other custom meta type added.

```php
$post_meta = Meta_Data_Inspector::initialise()->find_post_meta('post', 'my_key');
$this->assertInstanceOf(Meta_Data_Entity::class, $post_meta);
$this->assertEquals('This is my meta field', $post_meta->description);
```

**[Read More](docs/Meta_Data_Inspector.md)**

## WP Dependencies

Allows for the quick and simple installation of themes and plugins from remote sources.

```php
WP_Dependencies::install_remote_plugin_from_zip(
    'https://the-url.tc/woocommerce.zip', 'path/to/test_wp/root/'
);
WP_Dependencies::activate_plugin('woocommerce/woocommerce.php');
```

**[Read More](docs/WP_Dependencies.md)**

## Object (Reflection wrappers)

Reflection is super useful in testing, especially if you cant access internal properties and methods to create your tests. Or you need to mock parts of the process, which are otherwise not accessible (internal WP States etc).
_These also work on static methods/properties_

```php
//  Access protected & privates properties.
Objects::get_property($instnace, 'property');
// Set protected or private properties.
Objects::set_property($instnace, 'property', 'new value');
// Invoke private or protected method.
Objects::invoke_method($instance, 'method', ['the', 'args']);
```

**[Read More](docs/Objects.md)**

## Utils 

A collection of functions that have no other real place.
```php 
// iterable_map_with allows array_map to be done with access to the key and as many other
// values you wish to pass.
$result = Utils::iterable_map_with( 

    function($key, $value, $spacer){
        return $key . $spacer . $value;
    }, 
    ['key1'=>'value1', 'key2' => 'value2'],
    ' -|- '

); 
var_dump($result); // ['key1 -|- value1', 'key2 -|- value2']

```
**[Read More](docs/Utils.md)**

## Output

There are a number of methods that can be used to capture output from a function or method. This is useful for testing output from a function or method.

**[Read More](docs/Output.md)**

## Logable WPDB

This simple class which extends `wpdb` can be used for Application tests where you need to either mock the return from a WPDB call or log all wpdb calls made.

> Supports all public and common methods.

```php
$wpdb = new Logable_WPDB();

// Set a return value.
$wpdb->then_return = 1;

// Mock an insert 
$result = $wpdb->insert('table_name', ['col1'=>'value1'], ['%s']);

// Get back the set return value
var_dump($result);

// Access the useage log
$log = $wpdb->usage_log;
var_dump($log);
/**
 * ['insert' => 
 *   [0] => [
 *     'table' => 'table_name',
 *     'data' => ['col1'=>'value1'],
 *     'format' => ['%s'],
 *   ]
 * ]
 */
```
**[Read More](docs/Utils.md)**

## WP Unit TestCase Helper Traits

These traits are designed to be used with the WP Unit TestCase. They provide a number of helper functions to make testing easier.

**[Read More](docs/WP_Unit_TestCase_Helper_Traits.md)**

## Change log
* 1.1.1 - Updates dependencies, added in missing docs, missing tests and renamed the `array_map_with` to `iterable_map_with` to better reflect the use case.
* 1.1.0 - Replaced all instance of pipe() from Function_Constructors with compose() from Function_Constructors.
* 1.0.7 - Updated dependencies for Function_Constructors and testing.
* 1.0.6 - Added Logable WPDB, Extended Meta Data Inspector to make use of Comment Meta, Extended to PHP8.1 Support
* 1.0.5 - Update dependencies for php8, also added `plugin_installed` and `plugin_active` to `WP_Dependencies`
* 1.0.4 - Update all dependencies
* 1.0.3 - Clear up issue with the errors found in 1.0.2 but not in dev
* 1.0.2 - Uses menu_page_url for menu page urls and Menu_Page_Inspector given find_group function as the current naming is confusing
* 1.0.1 - Added in Meta_Data_Inspector for checking all registered meta data.
* 1.0.0 - Most in place now, still needs more docs and some extra tests on output.
