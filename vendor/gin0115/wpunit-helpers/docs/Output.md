# OBJECTS

The Output class is a tool which allows for the output of data to be captured and tested.

## Methods

### Output::buffer( callable $callback ): string

This allows you to capture the output of a function, and return it as a string.

```php
$output = Output::buffer(function(){
    echo 'Hello World';
});

$this->assertEquals('Hello World', $output);
```

> This is useful for testing the output of a function, or for testing the output of a view or a wp function call.

### Output::println( $value ): void

This function allows for the output of a string or stringable variable to be printed, with a new line at the end (PHP_EOL).

```php
Output::println('Hello World');
Output::println('Hello World');

// Output:
// Hello World
// Hello World

```