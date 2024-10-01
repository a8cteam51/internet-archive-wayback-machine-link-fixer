# The PinkCrab FunctionConstructors library.

[![PHPUnit](https://github.com/gin0115/pinkcrab_function_constructors/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/gin0115/pinkcrab_function_constructors/actions/workflows/php.yml) [![codecov](https://codecov.io/gh/gin0115/pinkcrab_function_constructors/branch/develop/graph/badge.svg?token=X4LS5961T1)](https://codecov.io/gh/gin0115/pinkcrab_function_constructors) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gin0115/pinkcrab_function_constructors/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gin0115/pinkcrab_function_constructors/?branch=master) ![Packagist Downloads](https://img.shields.io/packagist/dt/pinkcrab/function-constructors?label=Downloads) ![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/gin0115/pinkcrab_function_constructors?label=Latest)

*******

This library provides a small selection of functions for making functional programming a little cleaner and easier in php.

## Setup

Can be included into your project using either composer or added manually to your codebase.

### Via Composer

 `$ composer require pinkcrab/function-constructors`

### Via Manual Loader

If you wish to use this library within WordPress or other PHP codebase where you do not or cannot use composer, you can use the **FunctionsLoader** class. Just clone the repo into your codebase and do the following.

```php
require_once 'path/to/cloned/repo/FunctionsLoader.php';
FunctionsLoader::include(); 
```

All of our functions are namespaced as **PinkCrab\FunctionConstructors\\{lib}**. So the easiest way to use them is to use with an alias. Throughout all the docs on the wiki we use the following aliases.

```php
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\Comparisons as C;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;
use PinkCrab\FunctionConstructors\Objects as Obj;

// Allowing for
Arr\Map('esc_html') or Str\append('foo') or F\pipe($var, 'strtoupper', Str\append('foo'))
```

## Usage

At its core, the Function Constructors library is designed to make using PHP easier to use in a functional manor. With the use of functions `compose()` and `pipe()` its possible to construct complex functions, from simpler ones.

### Function Composition and Piping

#### pipe()

> PLEASE NOTE THIS HAS CHANGED IN VERSION 2.0.0, using `compose()` is now the preferred method.

Using `pipe(mixed $value, callable ...$callables)` and [ `pipeR()` *](#pipe "Same as pipe(), but callables in reverse order"), allows you to pass a value through a chain of callables. The result of the 1st function, is passed as the input the 2nd and so on, until the end when the final result is returned.

> The rest of this library makes it easier to use standard php functions as callables, by defining some of the parameters up front.

```php
$data = [0,3,4,5,6,8,4,6,8,1,3,4];

// Remove all odd numbers, sort in an acceding order and double the value.
$newData = F\pipe(
    $data,
    Arr\filter(Num\isMultipleOf(2)), // Remove odd numbers
    Arr\natsort(),                 // Sort the remaining values
    Arr\map(Num\multiply(2))       // Double the values.
);

// Result
$newData = [
 2 => 8,
 6 => 8,
 11 => 8,
 4 => 12,
 7 => 12,
 5 => 16,
 8 => 16,
];
```

#### compose()

Piping is ideal when you are working with a single value, but when it comes to working with Arrays or writing callbacks, compose() is much more useful.

`compose(callable ...$callables)` , `composeR(callable ...$callables)` , `composeSafe(callable ...$callables)` and `composeTypeSafe(callable $validator, callable ...$callables)` all allow you to create custom Closures.

```php

$data = [
    ['details'=>['description' => '    This is some description ']],
    ['details'=>['description' => '        This is some other description    ']],
];

$callback = F\compose(
   F\pluckProperty('details','description'), // Plucks the description
   'trim',                                   // Remove all whitespace
   Str\slice(0, 20),                         // Remove all but first 20 chars          
   'ucfirst',                                // Uppercase each word
   Str\prepend('...')                        // End the string with ...
);

$results = array_map($callback, $data);

$results = [
    'This Is Some Descrip...',
    'This Is Some Other D...'
]
```

> You can use `composeTypeSafe()` if you want to pass the return of each callable through a validator before being passed to the next. If the validator fails, the rest of the chain will be skipped and null will be returned.

*****

### Working with Records

It is possible to work with the properties of *Records* (arrays and objects). Indexes or Properties can be checked, fetched and set using some of the `GeneralFunctions` . 

#### Reading Properties

You can check if a property exists, get its value or compare it an defined value.

```php
$data = [
    ['id' => 1, 'name' => 'James', 'timezone' => '+1', 'colour' => 'red'],
    ['id' => 2, 'name' => 'Sam', 'timezone' => '+1', 'colour' => 'red', 'special' => true],
    ['id' => 3, 'name' => 'Sarah', 'timezone' => '+2', 'colour' => 'green'],
    ['id' => 4, 'name' => 'Donna', 'timezone' => '+2', 'colour' => 'blue', 'special' => true],
];

// Filter all users with +2 timezone.
$zonePlus2 = array_filter($data, F\propertyEquals('timezone','+2'));
$results = [['id' => 3, ....],['id' => 4, ...]];

// Filter all user who have the special index.
$special = array_filter($data, F\hasProperty('special'));
$results = [['id' => 2, ....],['id' => 4, ...]];

// Get a list of all colours.
$colours = array_map(F\getProperty('colour'), $data);
$results = ['red', 'red', 'green', 'blue'];
```

> `pluckProperty()` can also be used if you need to traverse nested properties/indexes of either **arrays** or **objects** *also handles `ArrayAccess` objects, set with array syntax* [see example on `compose()` ](#compose)

#### Writing Properties

Its also possible to write properties of objects and set values to indexes in arrays using the `setProperty()` function. More complex structures can also be created using the [Record Encoder](../../../../pinkcrab_function_constructors/wiki/Record_Encoder)

```php
// Set object property.
$object = new class(){ public $key = 'default'};

// Create a custom setter function.
$setKeyOfObject = F\setProperty($object, 'key');
$object = $setKeyOfObject('new value');
// {"key":"new value"}

// Can be used with arrays too
$array = ['key' => 'default'];

// Create a custom setter function.
$setKeyOfSArray = F\setProperty($array, 'key');
$array = $setKeyOfSArray('new value');
// [key => "new value"]
```

*****

### String Functions

Much of the string functions found in this library act as wrappers for common standard (PHP) library functions, but curried to allow them to be easier composed with.

#### String Manipulation

There is a collection of functions with make for the concatenation of strings.

```php
$appendFoo = Str\append('foo');
$result = $appendFoo('BAR');

$prependFoo = Str\prepend('foo');
$result = $prependFoo('BAR');

$replaceFooWithBar = Str\replaceWith('foo', 'bar');
$result = $replaceFooWithBar("its all a bit foo foo");
// "its all a bit bar bar"

$wrapStringWithBar = Str\wrap('bar-start-', '-bar-end');
$result = $wrapStringWithBar('foo');
// bar-start-foo-bar-end
```

#### String Contents

There is a collection of functions that be used to check the contents of a string.

```php
// Check if a string contains
$containsFoo = Str\contains('foo');
$containsFoo('foo');   // true
$containsFoo('fobar'); // false

// Check if string start with (ends with also included)
$startsBar = Str\startsWith('bar');
$startsBar('bar-foo'); // true
$startsBar('foo-bar'); // false

// Check if a blank string
Str\isBlank('');   // true
Str\isBlank(' ');  // false

// Unlike using empty(), this checks if the value is a string also.
Str\isBlank(0);    // false 
Str\isBlank(null); // false

// Contains a regex pattern
$containsNumber = Str\containsPattern('~[0-9]+~');
$containsNumber('apple');   // false
$containsNumber('A12DFR3'); // true
```

> `Str\isBlank()` can be used when composing a function, thanks to the Functions::isBlank constant.

```php
$data = [0 => '', 1 => 'fff', 2 => '    '];
$notBlanks = array_filter(PinkCrab\FunctionConstructors\Functions::IS_BLANK, $data);
// [0 => ''] 
```

#### Sub Strings

There is a series of functions that can be used to work with substrings.

```php
// Split the string into sub string
$inFours = Str\split(4);
$split = $inFours('AAAABBBBCCCCDDDD');
// ['AAAA','BBBB','CCCC','DDDD']

// Chunk the string
$in5s = Str\chunk(5, '-');
$result = $in5s('aaaaabbbbbccccc');
// 'aaaaa-bbbbb-ccccc-'

// Count all characters in a given string.
$charCount = Str\countChars();
$results = $charCount('Hello World');
// [32 => 1, 72 => 1, 87 => 1, 100 => 1, 101 => 1, 108 => 3, 111 => 2, 114 => 1]
// If the keys are mapped using chr(), you will get 
$results = (Arr\mapKey('chr')($results));
// ['H' => 1,'e' => 1,'l' => 3,'o' => 2,' ' => 1,'W' => 1,'r' => 1,'d' => 1,]

// Count occurrences of a substring.
$countFoo = Str\countSubString('foo');
$results = $countFoo('foo is foo and bar is not foo');
// 3

// Find the first position of foo in string.
$firstFoo = Str\firstPosition('foo');
$result = $firstFoo('abcdefoog');
// 5
```

> See more of the Strings functions [on the wiki](../../../../pinkcrab_function_constructors/wiki/Strings)

*****

### Number Functions

Much of the number functions found in this library act as wrappers for common standard (PHP) library functions, but curried to allow them to be easier composed with.

#### Basic Arithmetic 

You can do some basic arithmetic using composable functions. This allows for the creation of a base value, then work using the passed value.

> All these functions allow the use of `INT` or `FLOAT` only, all numerical strings must be cast before being used. Will throw `TypeError` otherwise.

```php
// Add
$addTo5 = Num\sum(5);

$addTo5(15.5); // 20.5
$addTo5(-2); // 3

// Subtract
$subtractFrom10 = Num\subtract(10);
$subtractFrom10(3)  // 7
$subtractFrom10(20) // -10

// Multiply 
$multiplyBy10 = Num\multiply(10)
$multiplyBy10(5);   // 50 
$multiplyBy10(2.5); // 25.0

// Divide By
$divideBy3 = Num\divideBy(3);
$divideBy3(12); // 4 = 12/3
$divideBy3(10); // 3.333333333333 

// Divide Into
$divideInto12 = Num\divideInto(12);
$divideInto12(4); // 3 = 12/4
```

#### Multiple and Modulus

It is possible to do basic modulus operations and working out if a number has a whole factor of another.

```php
// Factor of 
$isMultipleOf2 = Num\isMultipleOf(2);
$isMultipleOf2(12); // true
$isMultipleOf2(13); // false

// Getting the remainder
$remainderBy2 = Num\remainderBy(2);
$remainderBy2(10); // 0 = (5 * 2) - 10
$remainderBy2(9);  // 1 = (4 * 2) - 9
```

### Array Functions

As you can imagine there are a large number of functions relating to arrays and working with them. 

#### Map

This library contains a large number of variations of `array_map` , these can all be pre composed, using the other functions to be extremely powerful and easy to follow.

```php
// Create a mapper which doubles the value.
$doubleIt = Arr\map( Num\multiply(2) );
$doubleIt([1,2,3,4]); // [2,4,6,8] 

// Create mapper to normalise array keys
$normaliseKeys = Arr\mapKey(F\compose(
    'strval',
    'trim',
    Str\replace(' ', '-')
    Str\prepend('__')
));

$normaliseKeys(1 => 'a', ' 2 ' => 'b', 'some key' => 'c');
// ['__1'=> 'a', '__2' => 'b', '__some-key' => 'c']

// Map and array with the value and key.
$mapWithKey = Arr\mapWithKey( function($key, $value) {
    return $key . $value;
});
$mapWithKey('a' => 'pple', 'b' => 'anana'); 
// ['apple', 'banana']
```

> There is `flatMap()` and `mapWith()` also included, please see the wiki.

#### Filter and Take

There is a large number of composible functions based around `array_filter()` . Combined with a basic set of `take*()` functions, you can compose functions to work with lists/collections much easier.

```php
// Filter out ony factors of 3
$factorsOf3s = Arr\filter( Num\factorOf(3) );
$factorsOf3s([1,3,5,6,8,7,9,11]); // [3,6,9]

// Filer first and last of an array/
$games = [
    ['id'=>1, 'result'=>'loss'],
    ['id'=>2, 'result'=>'loss'],
    ['id'=>3, 'result'=>'win'],
    ['id'=>4, 'result'=>'win'],
    ['id'=>5, 'result'=>'loss'],
];

$firstWin = Arr\filterFirst( F\propertyEquals('result','win') );
$result = $firstWin($games); // ['id'=>3, 'result'=>'win']

$lastLoss = Arr\filterLast( F\propertyEquals('result','loss') );
$result = $lastLoss($games); // ['id'=>5, 'result'=>'loss']

// Count result of filter.
$totalWins = Arr\filterCount( F\propertyEquals('result','win') );
$result = $totalWins($games); // 2
```

> Filter is great if you want to just process every result in the collection, the `take()` family of functions allow for controlling how much of an array is filtered

```php
// Take the first or last items from an array
$first5 = Arr\take(5);
$last3 = Arr\takeLast(5);

$nums = [1,3,5,6,8,4,1,3,5,7,9,3,4];
$first5($nums); // [1,3,5,6,8]
$last3($nums);  // [9,3,4]

// Using takeWhile and takeUntil to get the same result.
$games = [
    ['id'=>1, 'result'=>'loss'],
    ['id'=>2, 'result'=>'loss'],
    ['id'=>3, 'result'=>'win'],
    ['id'=>4, 'result'=>'win'],
    ['id'=>5, 'result'=>'loss'],
];

// All games while the result is a loss, then stop
$initialLoosingStreak = Arr\takeWhile(F\propertyEquals('result','loss'));
// All games until the first win, then stop
$untilFirstWin = Arr\takeUntil(F\propertyEquals('result', 'win'));

$result = $initialLoosingStreak($game);
$result = $untilFirstWin($game);
// [['id' => 1, 'result' => 'loss'], ['id' => 2, 'result' => 'loss']]
```

#### Fold and Scan

Folding or reducing an a list is a pretty common operation and unlike the native `array_reduce` you have a little more flexibility.

```php

$payments = [
    'gfg1dg3d' => ['type' => 'card', 'amount' => 12.53],
    'eg43ytfh' => ['type' => 'cash', 'amount' => 21.95],
    '5g7tgxfb' => ['type' => 'card', 'amount' => 1.99],
    'oitu87uo' => ['type' => 'cash', 'amount' => 4.50],
    'ew1e5435' => ['type' => 'cash', 'amount' => 21.50],
];

// Get total for all cash payment.
$allCash = Arr\fold(function($total, $payment){
    if($payment['type'] === 'cash'){
        $total += $payment['amount'];
    }
    return $total;
},0.00);

$result = $allCash($payments); // 47.95

// Log all card payment in some class, with access to array keys.
$logCardPayments = Arr\foldKeys(function($log, $key, $payment){
    if($payment['type'] === 'card'){
        $log->addPayment(payment_key: $key, amount: $payment['amount']);
    }
    return $log;
}, new CardPaymentLog('some setup') );

$cardPaymentLog = $logCardPayments($payments);
var_dump($cardPayments->getPayments());
// [{'key': 'gfg1dg3d', 'amount': 12.53}, {'key': '5g7tgxfb', 'amount': 1.99}]

// Generate a running total of all payments.
$runningTotal = Arr\scan(function($runningTotal, $payment){
    $runningTotal += $payment['amount'];
    return $runningTotal;

}, 0.00);

$result = $runningTotal($payments);
// [0.0, 12.53, 34.48, 36.47, 40.97, 62.47]
```

> You also have access to `foldR()` and `scanR()` which will iterate through the array backwards.

#### Grouping and Partitioning 

Function Constructor has a number of functions which make it easy to group and partition arrays

```php
$data = [
    ['id'=>1, 'name'=>'John', 'age'=>20, 'someMetric' => 'A12'],
    ['id'=>2, 'name'=>'Jane', 'age'=>21, 'someMetric' => 'B10'],
    ['id'=>3, 'name'=>'Joe', 'age'=>20, 'someMetric' => 'C15'],
    ['id'=>4, 'name'=>'Jack', 'age'=>18, 'someMetric' => 'B10'],
    ['id'=>5, 'name'=>'Jill', 'age'=>22, 'someMetric' => 'A12'],
];

// Group by the return value of the function.
$groupedByMetric = Arr\groupBy(function($item){
    return $item['someMetric'];
});

$results = $groupedByMetric($data);
["A12" =>  [
    ["id" => 1,"name" => "John", ...],
    ["id" => 5,"name" => "Jill", ...]
],
"B10" =>  [
    ["id" => 2,"name" => "Jane", ...],
    ["id" => 4,"name" => "Jack", ...]
],
"C15" =>  [
    ["id" => 3,"name" => "Joe", ...]
]];

// Partition using a predicate function.
$over21 = Arr\partition(function($item){
    return $item['age'] >= 21;
});

$results = $over21($data);
[0 => [ // false values
    ["name" => "John", "age" => 20, ...],
    ["name" => "Joe", "age" => 20, ...],
    ["name" => "Jack", "age" => 18, ...]
],
1 => [ // true values
    ["name" => "Jane", "age" => 21, ...],
    ["name" => "Jill", "age" => 22, ...]
]];
```

> It is possible to chunk and split arrays, see the wiki for more.

#### Sorting

The native PHP `sort` functions are tricky with a functional approach, as they sort via reference, rather than by a return value. The Function Constructor library covers all native sorting as partially applied functions.

```php
// Sorting simple arrays
$dataWords = ['Zoo', 'cat', 'Dog', 'ant', 'bat', 'Cow']; 

$sortWords = Arr\sort(SORT_STRING);
$result = $sortWords($dataWords);
// ['ant', 'bat', 'cat', 'Cow', 'Dog', 'Zoo'];

// Sorting associative arrays
$dataBooks = [    
    'ehjf89' => ['id'=>'ehjf89', 'title'=>'Some title', 'author'=> 'Adam James'],
    'retg23' => ['id'=>'retg23', 'title'=>'A Title', 'author'=> 'Jane Jones'],
    'fvbi43' => ['id'=>'fvbi43', 'title'=>'Some title words', 'author'=> 'Sam Smith'],
    'mgged3' => ['id'=>'mgged3', 'title'=>'Book', 'author'=> 'Will Adams'],
]; 


// Sort by key
$sortBookByKey = Arr\ksort(SORT_STRING | SORT_FLAG_CASE);
$result = $sortBookByKey($dataBooks);
[
    'ehJF89' => ['id' => 'ehjf89', 'title' => 'Some title', 'author' => 'Adam James'],
    'fvbI43' => ['id' => 'fvbi43', 'title' => 'Some title words', 'author' => 'Sam Smith'],
    'MggEd3' => ['id' => 'mgged3', 'title' => 'Book', 'author' => 'Will Adams'],
    'Retg23' => ['id' => 'retg23', 'title' => 'A Title', 'author' => 'Jane Jones'],
]

// Sort by author
$sortBookByAuthor = Arr\uasort(function ($a, $b) {
    return strcmp($a['author'], $b['author']);
});
$sortBookByAuthor($dataBooks);
[
    'ehJF89' => ['id' => 'ehjf89', 'title' => 'Some title', 'author' => 'Adam James'],
    'Retg23' => ['id' => 'retg23', 'title' => 'A Title', 'author' => 'Jane Jones'],
    'fvbI43' => ['id' => 'fvbi43', 'title' => 'Some title words', 'author' => 'Sam Smith'],
    'MggEd3' => ['id' => 'mgged3', 'title' => 'Book', 'author' => 'Will Adams'],
]

```
****

### Contributions

If you would like to contribute to this project, please feel to fork the project on github and submit a pull request.

****

> For more details, please read the [wiki](https://github.com/gin0115/pinkcrab_function_constructors/wiki)

## Changes

* 0.2.0 - 
   * **New Functions**
   * `Numbers\isMultipleOf()`
   * `Numbers\isFactorOf()`
   * `Strings\isBlank()`
   * `Strings\splitByLength()`
   * `GeneralFunctions\ifThen()`
   * `GeneralFunctions\ifElse()`
   * `GeneralFunctions\composeR()`
   * `Arrays\fold()`
   * `Arrays\foldR()`
   * `Arrays\foldKey()`
   * `Arrays\scan()`
   * `Arrays\scanR()`
   * `Arrays\take()`
   * `Arrays\takeLast()`
   * `Arrays\takeUntil()`
   * `Arrays\takeWhile()`
   * `Arrays\filterAny()`
   * `Arrays\filterAll()`
   * `Arrays\mapWithKey()`
   * `Objects\isInstanceOf()`
   * `Objects\implementsInterface()`
   * `Objects\toArray()`
   * `Objects\usesTrait()`
   * `Objects\createWith()`
   * **Breaking Changes**
   * `GeneralFunctions\pipe()` & `GeneralFunctions\pipeR()` have now changed and are no longer alias for `compose()`
   * `GeneralFunctions\setProperty()` now takes the property argument when creating the Closure.
   * `Strings\tagWrap()` has been removed
   * `Strings\asUrl()` has been removed
   * `Strings\vSprintf()` has has its arguments reversed.
   * `Strings\split()` is now a wrapper for explode() and the existing `Strings\split()` has been renamed to `Strings\splitByLength()`
   * **Other Changes**
   * Constants added using the `Functions` class-name, `Functions::isBlank` can be used as a string for a callable.
   * `GeneralFunctions\toArray()` has been moved to `Objects\toArray()`, `Objects\toArray()` is now an alias for `GeneralFunctions\toArray()`

* 0.1.2 - Added `Arrays\zip()`

* 0.1.3 - Added` Arrays\filterKey()`
