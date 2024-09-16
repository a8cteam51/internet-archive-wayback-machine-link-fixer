# Utils

A collection of assorted functions which do not really have a single place to live.

> All methods in this helper class are self contained and called in via static methods.

## iterable_map_with()

```php
/**
 * Array map, which gives access to array key and a selection of static
 * values.
 *
 * @param callable $function
 * @param iterable<mixed> $data
 * @param mixed ...$with
 * @return array<int, mixed>
 */
public static function iterable_map_with( callable $function, iterable $data, ...$with ): array
```
As this uses a foreach loop at its heart, any iterable can be used although care should be taken as the key isn't returned from an iterator unless ```yield 'key'=>'value';``` is used
```php

$my_data = [ 'user_12342' => 'Matt Smith', 'user_23423' => 'Sally Jones' ];
$logged_in_user = 'user_12';
$banned_users = ['user_12342', 'another_key'];

$mapped = Utils::iterable_map_with(
    function($user_id, $user_name, $admin_user, $banned_users): string {        
        $user = new stdClass();
        $user->name = $user_name;
        $user->banned = in_array($user_id, $banned_users, true);
        $user->processed_by = $admin_user;
        return $user;
    }, 
    $my_data, $static_value, $banned_users 
);

// Can also be used with a generator.
$generator = function(): Generator {
    yield 'user_12342' => 'Matt Smith';
    yield 'user_23423' => 'Sally Jones';
    yield 'user_12432' => 'Joe Nobody';
};

$mapped = Utils::iterable_map_with(
    function($user_id, $user_name, $admin_user, $banned_users): string {...}, 
    $generator(), $static_value, $banned_users 
);
```

> The static parameters are not required, so this can be used as an array_map which gives access to both key and value.

```php 
Utils::iterable_map_with( function($key, $value){...}, $array );
```
## recursive_rmdir()
```php
/**
 * Recursively remove a directory and all its contents.
 *
 * @param string $dir
 * @return void
 */
public static function recursive_rmdir( string $dir ): void
```
This allows for the recursive removal of all files and sub directories within a directory. While you can do this using a directory iterator, this allows for a quick and cleanup of any mock data.

The following example can be cleared using ``` Utils::recursive_rmdir('parent');``` *Full path must be passed*

```
parent
│   README.md
│   file001.txt    
│
└───folder1
│   │   file011.txt
│   │   file012.txt
│   │
│   └───subfolder1
│       │   file111.txt
│       │   file112.txt
│   
└───folder2
    │   file021.txt
    │   file022.txt
```

## get_subdirectories()
```php
/**
 * Gets an array of all top level sub directories from a parent directory.
 *
 * @param string $dir
 * @return array<string>
 */
public static function get_subdirectories( string $dir ): array
```
Sometimes you just need to know the current list of subdirectories, without filtering out files and . , ..

get_subdirectories() allows you to get the next generate of directories either.

```php
// Using dir tree example from recursive_rmdir() docs.
$sub_directories = Utils::get_subdirectories('parent');
print_r($sub_directories); // ['folder1', 'folder2']
```
If a none existent dir path, passed, will just return an empty array.

