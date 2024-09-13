# Meta_Box_Inspector

Working with the $wp_meta_box global can be fun at times, meta boxes are deeply nested in a multidimensional array of or arrays. Finding if your metabox has been registered to a post type and its details, is painful. Especially in intergration style tests.

The Meta_Box_Inspector class will map all registered meta boxes to a **Meta_Box_Entity** object, where all values can be recalled and tested against. You can even render the view of a meta box from any WP_Post instance.

## Setup

``` php
$inspector = new Meta_Box_Inspector;
```

Once you created an instance, you will need to populate the internal $wp_meta_boxes global and map them into a more workable format.

```php 
$inspector->maybe_register()->set_meta_boxes(); 
``` 

```maybe_register()``` will only run ```do_action('add_meta_boxes')``` if the global value is empty (prevents adding the same meta boxes multiple times).

## Static Construction

To make the setup process a little more user friendly, we have added a simple static initialiser. This allow you to do all as one liner. 
```php
$inspector = Meta_Box_Inspector::initialise();
// Same as 
$inspector = new Meta_Box_Inspector();
$inspector->maybe_register()->set_meta_boxes(); 
```
You can even one liner it as part of your tests
```php
$this->assertEqual(
    'My Metabox', 
    Meta_Box_Inspector::initialise()->find('my_key')->title
);
```

## Find by ID or Post Type

You can easily search all of your meta boxes by either the ID or by a single (or multiple) post types.

```php 
// Find based on key/id
$found = $inspector->find('my_meta_box_by_id');
var_dump($found); // Either instance of Meta_Box_Entity or null if not found.

// Find all based on post type
$found = $inspector->for_post_types('post', 'page', 'my_cpt');
var_dump($found); // Array of Meta_Box_Entity matching post types.
```

> Please note if a meta box is to show on multiple post types, it will be included multiple times. When calling find() it will reutrn the first matching value it finds based on the key. If you need to get all instances (for all post types), please use filter()

## Filter

You can pass a function to be used to run array_filter on the internal set of meta boxes. This allows you to find more specific meta boxes based on as many values as you need. Returns an array of matching **Meta_Box_Entity**

``` php
$found = $inspector->filter(
    function(Meta_Box_Entity $box): bool{
        return $box->post_type === 'my_cpt' 
            && $box->postition === 'side';    
    }
); 
var_dump($found); // Array of all matching Meta_Box_Entity objects.
```

_Included as a dependency to this library (and used in its source) is the PinkCrab FunctionConstructors libray, which has many helpful functions to help with is._
``` php
use PinkCrab\FunctionConstructors\Comparisons as C; // Use whatever alias you wish, these are my preference
use PinkCrab\FunctionConstructors\GeneralFunctions as F; 

$found = $inspector->filter( C\all( // All must be true
    F\propertyEquals( 'post_type', 'my_cpt' ), // Get the property and does a strict check
    F\propertyEquals( 'postition', 'side' )
)); 
```

## Render

If you ever need to test you template or any logic around setting/getting meta data, you can render your meta box for any WP_Post instance. This allows for quick and easy mocking.

```php
$mock_post = \get_post( $this->factory->post->create( /* Pass any values to create with here */ ); 
$found = $inspector->find('my_meta_box_by_id'); 

// Check you have a metabox.
if( ! is_null( $found ) ){
    $inspector->render_meta_box($found, $mock_post);
}

// This will then render the meta box. You can either use 
$this->assertOutputString('Find'); 

// Or the Output::buffer() method included in this library.
$output = Output::buffer(
    function() use ($inspector, $mock_post, $found) : void{
        $inspector->render_meta_box($found, $mock_post);
    }
); 

$this->assertStringContainsString('Find', $output);
$this->assertStringContainsString('more', $output);

```

# Object Methods & Properties.

## Gin0115\WPUnit_Helpers\WP\Meta_Box_Inspector

### Properties

```php 
/** @var array<int, Meta_Box_Entity> */ 
public array $meta_boxes;
```
Holds the mapped meta box instances.

### Methods

```php 
/**
 * Self contained, initliaser
 *
 * @return Meta_Box_Inspector
 */
public static function initialise(): Meta_Box_Inspector 
```
Used to create a populated instance of the inspector. Should only be used if you know for sure your metaboxes have already been registered.
```php
/**
 * Returns all the current meta_boxes, or null if not set.
 * Fire add_meta_boxes to add any waiting.
 *
 * @return array<string, array>|null
 */
public function from_global(): ?array {...}
```
Used to grab all of the meta box definitions from the wp globals. These are not mapped and are returned as is. As with the underlying globals, will reutrn null if not currently set.

```php 
/**
 * Registers meta_boxes, if they have not already been set.
 *
 * @return self
 */
public function maybe_register(): self {...}
```
Will register all meta boxes if not already set. This checks the current state and if null, wil call ```do_action( 'add_meta_boxes' );```. Reutrns the same instance, so can be used in chained/fluent calls.

```php 
/**
 * Starts the mapping process.
 *
 * @return self
 */
public function set_meta_boxes(): self {...}
```
Hydrates all currently defined meta boxes (from globals) to Meta_Box_Entity and hold in internal $meta_boxes array.

```php 
/**
 * Returns all meta_boxes for a multiple post types.
 *
 * @param string ...$post_type
 * @return array<Meta_Box_Entity>
 */
public for_post_types( string ...$post_type ): array {...}
```
Allows the passing of multiple post types, returns back all metaboxes for the post types.

```php 
/**
 * Attempts to find a meta_box based on id.
 * Returns first instance found.
 *
 * @param string $id
 * @return Meta_Box_Entity|null
 */
public function find( string $id ): ?Meta_Box_Entity {...}
```
Searches for a meta box based on the id/key it was defined under. Returns back the first instance it finds, regardless of post type.
> See details above about meta boxes defined to multiple post types.

```php
/**
 * Allows the filtering of meta boxes.
 *
 * @param callable $filter
 * @return array<Meta_Box_Entity>
 */
public function filter( callable $filter ): array {...}
```
Allows for the passing of a filter to extract meta boxes based on any criteria.

```php
/**
 * Renders a meta_box based on a post type passed.
 * Prints the contents!
 *
 * @param Meta_Box_Entity $meta_box
 * @param \WP_Post $post
 * @return void
 */
public function render_meta_box( 
    Meta_Box_Entity $meta_box, 
    \WP_Post $post 
): void {...}
```
Renders (and prints) a defined meta box (from its isntance) and based on a post. This allows for the mocking of post types (and meta/terms), for checking views are rendered as expected.

## Gin0115\WPUnit_Helpers\WP\Entities\Meta_Box_Entity

### Properties
```php 
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
```
> Please note as the callback is held as a property, you will need to extract this before trying to call. 

```php 
$callback = $meta_box->callback; 
$callback( $post, $meta_box->args ); 
```


