# Menu_Data_Inspector

The Meta Data inspector gives you quick and easy access to all pre registered meta data. This includes Post, Term, User, Comment and any custom types defined.

## Setup

To create the inspector, its jsut a case of letting all your meta be registered. either by calling your action they are hooked into or just activating your plugin in the test bootstrap.

``` php
$inspector = new Meta_Data_Inspector;
$inspector->set_registered_meta_data(); 
//or 
$inspector = Meta_Data_Inspector::initialise();
```

**You can repopulate the internal state of the inspector from the globals by calling**
```php
$inspector = $inspector->set_registered_meta_data(true);
```
*This will rebuild the internal state of the inspector. WP Will not included your meta again, so you dont need to clear the internal state before running.*

## Post Types
The most common form of meta used in WP is post meta. The Inspector will allow you to check if a meta key has been registered against a post type and to get all meta fields registered for any post type.


### find_post_meta(string $post_type, string $meta_key): ? Meta_Data_Entity
You can search for a registered post meta key, if found will return a populated Meta Data Entity or null if not found.
```php 
// Find based on  meta key
$inspector = Meta_Data_Inspector::initialise();
$found = $inspector->find_post_meta('post', 'post_meta_key_1');
var_dump($found); // Either instance of Meta_Data_Entity or null if not found.
$this->assertEquals('post', $found->sub_type);
```

### for_post_types(string ...$post_types): array<Meta_Data_Entity>
You can find all meta for a single or multiple post types. Will return an array of all post meta found for the defined post types
```php
$inspector = Meta_Data_Inspector::initialise();
$meta = $inspector->find_post_meta('post', 'page');
var_dump($meta); //[Meta_Data_Entity, Meta_Data_Entity,Meta_Data_Entity];

$expected = ['post_meta1', 'post_meta2', 'page_meta1'];
$this->assertCount(count($expected), $meta);
foreach($meta as $value){
    $this->assertInArray($meta->meta_key, $expected);
}
```
## Taxonomy/Term

### find_term_meta(string $taxonomy, string $meta_key): ? Meta_Data_Entity
You can search for a registered term meta key, if found will return a populated Meta Data Entity or null if not found.

```php 
// Find based on  meta key
$inspector = Meta_Data_Inspector::initialise();
$found = $inspector->find_term_meta('post_tag', 'term_meta_key_1');
var_dump($found); // Either instance of Meta_Data_Entity or null if not found.
$this->assertEquals('post_tag', $found->sub_type);
```

### for_taxonomies(string ...$taxonomies): array<Meta_Data_Entity>
You can find all meta for a single or multiple post types. Will return an array of all term meta found for the defined taxonmies
```php
$inspector = Meta_Data_Inspector::initialise();
$meta = $inspector->find_term_meta('my_taxonomy');
var_dump($meta); //[Meta_Data_Entity, Meta_Data_Entity];

$expected = ['term_meta1', 'term_meta2'];
$this->assertCount(count($expected), $meta);
foreach($meta as $value){
    $this->assertInArray($meta->meta_key, $expected);
}
```
## User

### find_user_meta(string $meta_key): ? Meta_Data_Entity
You can search for a registered user meta key, if found will return a populated Meta Data Entity or null if not found.

```php 
// Find based on user meta key
$inspector = Meta_Data_Inspector::initialise();
$found = $inspector->find_user_meta('users_account_ref');
var_dump($found); // Either instance of Meta_Data_Entity or null if not found.
$this->assertNotNull($found);
```
## Comment

### find_comment_meta(string $meta_key): ? Meta_Data_Entity
You can search for a registered comment meta key, if found will return a populated Meta Data Entity or null if not found.

```php 
// Find based on comment meta key
$inspector = Meta_Data_Inspector::initialise();
$found = $inspector->find_comment_meta('commenter_account_ref');
var_dump($found); // Either instance of Meta_Data_Entity or null if not found.
$this->assertNotNull($found);
```

# Object Methods & Properties


### filter(callable $filter): array<Meta_Data_Entity>
This allows for creating more complex queries against all registered meta data.
```php 
$inspector = Meta_Data_Inspector::initialise();
$found = $inspector->filter(function(Meta_Data_Entity $meta): bool{
    return $meta->show_in_rest !== false;
});
var_dump($found); // Will have all registered meta which has defined rest schema.
```

### set_registered_meta_data(bool $force_reset = false): Meta_Data_Inspector
This grabs all current meta data from the WP Globals and sets to the inspector. Passing `TRUE` will reset all the internal listings from Globals. Should only be forced if you need to reset the internal state to check something was added afterwards.