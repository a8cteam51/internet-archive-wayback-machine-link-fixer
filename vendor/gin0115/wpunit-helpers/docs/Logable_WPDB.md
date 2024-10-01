# Logable WPDB

It is possible to use a mock version of WPDB, which will log all calls made to it. This is useful for debugging, and for testing that the correct queries are being made.

## Properties

There are 2 custom properties which are added to the class.

### then_return

This allows you to set a return value for the next call made to the class. This is useful for mocking the return value of a method.

```php
$wpdb = new Logable_WPDB();
$wpdb->then_return = 'DONE';

$result = $wpdb->insert('table_name', ['col1'=>'value1'], ['%s']);
var_dump($result); // 'DONE'
```

> This is useful for mocking the return value of a method for tests.

### usage_log

This is an array which contains all the calls made to the class. It is an array of arrays, with the first key being the method name, and the value being an array of all the arguments passed to the method.

```php
$wpdb = new Logable_WPDB();
$wpdb->insert('table_name', ['col1'=>'value1'], ['%s']);

$log = $wpdb->usage_log;
var_dump($log);
/**
 * ['insert' =>
 *  ['table_name'] => [
 *      'data' => ['col1'=>'value1'],
 *      'format' => ['%s']
 *  ]
 * ]
 */
```

> This is useful for debugging, and for testing that the correct queries are being made.

## Methods

The following methods will be logged and return the the `then_return` value.

* insert()
* get_results()
* prepare()
* query()
* replace()
* update()
* delete()
* get_var()
* get_row()
* get_col()


