<?php

declare(strict_types=1);

require_once dirname(__FILE__, 2) . '/FunctionsLoader.php';

/**
 * Tests for the Array functions class.
 *
 * @since 0.1.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;
use PinkCrab\FunctionConstructors\GeneralFunctions as Func;

use function PHPUnit\Framework\throwException;

/**
 * ArrayFunction class.
 */
class ArrayFunctionTests extends TestCase
{
    /**
     * Random pollyfills
     */
    public function __call($method, $params)
    {
        switch ($method) {
            case 'assertIsArray':
                $this->assertTrue(is_array($params));
                break;
        }
    }

    public function setup(): void
    {
        FunctionsLoader::include();
    }

    public function testCanPushToHead(): void
    {
        $pushToHead = Arr\pushHead(array(3, 4, 5, 6));
        $added2     = $pushToHead(2);
        $this->assertEquals(2, $added2[0]);

        $pushToHead = Arr\pushHead($added2);
        $added1     = $pushToHead(1);
        $this->assertEquals(1, $added1[0]);

        // As curried.
        $curried = Arr\pushHead(array(3, 4, 5, 6))(2);
        $this->assertEquals(2, $curried[0]);
    }

    public function testCanPushToTail(): void
    {
        $pushToTail = Arr\pushTail(array(1, 2, 3, 4));
        $added2     = $pushToTail(5);
        $this->assertEquals(5, $added2[4]);

        $pushToTail = Arr\pushTail($added2);
        $added1     = $pushToTail(6);
        $this->assertEquals(6, $added1[5]);

        // As curried.
        $curried = Arr\pushTail(array(1, 2, 3, 4))(5);
        $this->assertEquals(5, $curried[4]);
    }

    public function testCanUseTail()
    {
        $names = array('Sam Smith', 'Barry Smith', 'Sam Power', 'Rebecca Smith');
        $this->assertEquals('Rebecca Smith', Arr\tail($names));
        // Check returns null if empty.
        $this->assertNull(Arr\tail(array()));
    }

    public function testCanUseHead()
    {
        $names = array('Sam Smith', 'Barry Smith', 'Sam Power', 'Rebecca Smith');
        $this->assertEquals('Sam Smith', Arr\head($names));
        // Check returns null if empty.
        $this->assertNull(Arr\head(array()));
    }

    public function testCanCompileArray(): void
    {
        $arrayCompiler = Arr\arrayCompiler();
        $arrayCompiler = $arrayCompiler('Hello');
        $arrayCompiler = $arrayCompiler('ERROR');
        $this->assertEquals('Hello', $arrayCompiler()[0]);
        $this->assertEquals('ERROR', $arrayCompiler()[1]);

        // As curried
        $arrayCompiler = $arrayCompiler('NO PLEASE GOD NO')('what')('more');
        $this->assertEquals('NO PLEASE GOD NO', $arrayCompiler()[2]);
        $this->assertEquals('what', $arrayCompiler()[3]);
        $this->assertEquals('more', $arrayCompiler()[4]);

        // Test can output on invoke.
        $this->assertTrue(is_array($arrayCompiler()));
    }

    public function testCanCompileArrayTyped()
    {
        $arrayCompiler = Arr\arrayCompilerTyped('is_string');
        $arrayCompiler = $arrayCompiler('Hello');
        $arrayCompiler = $arrayCompiler('ERROR');
        $arrayCompiler = $arrayCompiler(array('ERROR'));
        $this->assertCount(2, $arrayCompiler());

        $arrayCompiler = $arrayCompiler('Hello')(1)(NAN)('so 4?');
        $this->assertCount(4, $arrayCompiler());
    }

    public function testCanGroupByArray(): void
    {
        $groupByPerfectNumbers = Arr\groupBy(
            function ($e) {
                return in_array($e, array(1, 2, 3, 6, 12)) ? 'Perfect' : 'Not Perfect';
            }
        );

        $grouped = $groupByPerfectNumbers(range(1, 12));

        $this->assertArrayHasKey('Perfect', $grouped);
        $this->assertArrayHasKey('Not Perfect', $grouped);
        $this->assertContains(1, $grouped['Perfect']);
        $this->assertContains(2, $grouped['Perfect']);
        $this->assertContains(3, $grouped['Perfect']);
        $this->assertContains(5, $grouped['Not Perfect']);
        $this->assertContains(7, $grouped['Not Perfect']);
        $this->assertContains(9, $grouped['Not Perfect']);
    }

    public function testCanChunk(): void
    {
        $chunkIn5     = Arr\chunk(5);
        $chunkedRange = $chunkIn5(range(1, 500));
        $this->assertCount(100, $chunkedRange);
        $this->assertCount(5, $chunkedRange[5]);
        $this->assertCount(5, $chunkedRange[78]);

        // Check that keys are retained.
        $chunkInPairs = Arr\chunk(2, true);
        $chunkedNames = $chunkInPairs(array('Jim', 'Bob', 'Gem', 'Fay'));
        $this->assertCount(2, $chunkedNames);
        $this->assertEquals('Bob', $chunkedNames[0][1]);
        $this->assertEquals('Fay', $chunkedNames[1][3]);
    }

    public function testCanUseZip()
    {
        $array = ['a', 'b', 'c'];

        // Missing Key.
        $arrayMissing = ['A', 'B'];
        $expectedMissing = [['a', 'A'], ['b', 'B'], ['c', 'FALLBACK']];
        $resultMissing = Arr\zip($arrayMissing, 'FALLBACK')($array);
        $this->assertSame($resultMissing, $expectedMissing);

        // Matching length.
        $arrayFull = ['A', 'B', 'C'];
        $expectedFull = [['a', 'A'], ['b', 'B'], ['c', 'C']];
        $resultFull = Arr\zip($arrayFull, 'FALLBACK')($array);
        $this->assertSame($resultFull, $expectedFull);
    }

    public function testCanUseColumn(): void
    {
        $data = array(
            array(
                'id'   => 41,
                'name' => 'Bob',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 26,
                'name' => 'Jane',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 53,
                'name' => 'Sam',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 64,
                'name' => 'Bev',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 55,
                'name' => 'Joan',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 644,
                'name' => 'Bazza',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 66667,
                'name' => 'Duke',
                'foo'  => rand(1, 7),
            ),
            array(
                'id'   => 666668,
                'name' => 'Guybrush',
                'foo'  => rand(1, 7),
            ),
        );

        $getUsersNames = Arr\column('name', 'id');
        $this->assertCount(8, $getUsersNames($data));
        $this->assertEquals('Bob', $getUsersNames($data)[41]);
        $this->assertEquals('Bev', $getUsersNames($data)[64]);
        $this->assertEquals('Guybrush', $getUsersNames($data)[666668]);

        $getUsersRandoms = Arr\column('foo', 'name');
        $this->assertCount(8, $getUsersRandoms($data));
        $this->assertArrayHasKey('Bev', $getUsersRandoms($data));
        $this->assertArrayHasKey('Duke', $getUsersRandoms($data));
        $this->assertArrayNotHasKey(2, $getUsersRandoms($data));
        $this->assertArrayHasKey('Bazza', $getUsersRandoms($data));
    }

    /** @testdox It should be possible to flatten an array by any number of nodes. */
    public function testCanFlattenArray(): void
    {
        $array = array(
            1,
            2,
            array(),
            array( 3, 4 ),
            array(
                5,
                6,
                7,
                8,
                array(
                    9,
                    10,
                    array(11, 12, 13),
                ),
            ),
        );
        $this->assertArrayHasKey(2, Arr\flattenByN(2)($array));
        $this->assertIsArray(Arr\flattenByN(1)($array)[8]);

        $this->assertArrayHasKey(9, Arr\flattenByN(2)($array));
        $this->assertIsArray(Arr\flattenByN(2)($array)[10]);

        $this->assertArrayHasKey(12, Arr\flattenByN(3)($array));
        $this->assertEquals(13, Arr\flattenByN(3)($array)[12]);

        // Check will fully flatten if no depth defined.
        $this->assertArrayHasKey(12, Arr\flattenByN()($array));
        $this->assertEquals(13, Arr\flattenByN()($array)[12]);
    }

    public function testCanUseReplace()
    {
        $base          = array('orange', 'banana', 'apple', 'raspberry');
        $replacements  = array(
            0 => 'pineapple',
            4 => 'cherry',
        );
        $replacements2 = array(0 => 'grape');

        $replaceItems = Arr\replace($replacements, $replacements2);

        $this->assertIsArray($replaceItems($base));
        $this->assertEquals('grape', $replaceItems($base)[0]);
        $this->assertEquals('banana', $replaceItems($base)[1]);
        $this->assertEquals('apple', $replaceItems($base)[2]);
        $this->assertEquals('raspberry', $replaceItems($base)[3]);
        $this->assertEquals('cherry', $replaceItems($base)[4]);
    }
    public function testCanUseReplaceRecursive(): void
    {
        $base = array(
            'citrus'  => array('orange'),
            'berries' => array('apple', 'raspberry'),
        );

        $replacements = array(
            'citrus'  => array('pineapple'),
            'berries' => array('blueberry'),
        );

        $replaceItems = Arr\replaceRecursive($replacements);

        $this->assertIsArray($replaceItems($base));
        $this->assertArrayHasKey('citrus', $replaceItems($base));
        $this->assertArrayHasKey('berries', $replaceItems($base));
        $this->assertEquals('pineapple', $replaceItems($base)['citrus'][0]);
        $this->assertEquals('raspberry', $replaceItems($base)['berries'][1]);
    }

    public function testCanUseSumWhere()
    {
        $data = array(
            (object) array(
                'id'   => 1,
                'cost' => 12.55,
            ),
            (object) array(
                'id'   => 3,
                'cost' => 2.45,
            ),
            (object) array(
                'id'   => 34,
                'cost' => 99.99,
            ),
            (object) array(
                'id'   => 12,
                'cost' => 100.01,
            ),
        );

        $costSum = Arr\sumWhere(
            function ($e) {
                return $e->cost;
            }
        );

        $this->assertEquals(215.00, $costSum($data));
    }



    public function testCanSortArray(): void
    {
        $array         = array('b', 'c', 'a', 'f', 'd', 'z', 'g');
        $sortAsStrings = Arr\sort(SORT_STRING);

        $sortedArray = $sortAsStrings($array);
        $this->assertEquals('a', $sortedArray[0]);
        $this->assertEquals('b', $sortedArray[1]);
        $this->assertEquals('c', $sortedArray[2]);
        $this->assertEquals('z', $sortedArray[6]);

        // Check hasnt changed inital array.
        $this->assertEquals('c', $array[1]);
        $this->assertEquals('a', $array[2]);
    }

    public function testCanDoUasortOnArray(): void
    {
        $array = array(
            'a' => 4,
            'b' => 8,
            'c' => -1,
            'd' => -9,
            'e' => 2,
            'f' => 5,
            'g' => 3,
            'h' => -4,
        );

        $lowestFirstCallback = function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        };

        $sortByLowest = Arr\uasort($lowestFirstCallback);

        // Still retains the key.
        $sortedArray = $sortByLowest($array);

        $this->assertEquals(-9, $sortedArray['d']);
        $this->assertEquals(-9, array_values($sortedArray)[0]);

        $this->assertEquals(2, $sortedArray['e']);
        $this->assertEquals(2, array_values($sortedArray)[3]);

        $this->assertEquals(8, $sortedArray['b']);
        $this->assertEquals(8, array_values($sortedArray)[7]);
    }


    public function testCanDoUsortOnArray(): void
    {
        $array = array(3, 2, 5, 6, 1);

        $lowestFirstCallback = function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        };

        $sortByLowest = Arr\usort($lowestFirstCallback);

        // Still retains the key.
        $sortedArray = $sortByLowest($array);

        $this->assertEquals(1, $sortedArray[0]);
        $this->assertEquals(2, $sortedArray[1]);
        $this->assertEquals(5, $sortedArray[3]);
        $this->assertEquals(6, $sortedArray[4]);
    }

    public function testCanPartitionTable()
    {
        $isEven = function ($e) {
            return $e % 2 === 0;
        };

        $sortByOddEven = Arr\partition($isEven);

        $data = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $sorted = $sortByOddEven($data);
        $this->assertCount(5, $sorted[0]);
        $this->assertCount(5, $sorted[1]);

        $this->assertContains(2, $sorted[1]);
        $this->assertContains(4, $sorted[1]);
        $this->assertContains(8, $sorted[1]);
        $this->assertContains(3, $sorted[0]);
        $this->assertContains(7, $sorted[0]);
    }

    public function testCanDoToString(): void
    {
        $source = array(
            'hi',
            'there',
            'how',
            'are',
            'you',
            3,
            12.7,
            true,
            null,
        );

        $noSpacing   = Arr\toString();
        $withSpacing = Arr\toString(' ');

        $this->assertEquals('hitherehowareyou312.71', $noSpacing($source));
        $this->assertEquals('hi there how are you 3 12.7 1 ', $withSpacing($source));
    }

    /** @testdox It should be possible to create a function which allows for calling scan on a passed array */
    public function testScanL(): void
    {
        $initial  = array( 1, 2, 3, 4, 5 );
        $expected = array( 0, 1, 3, 6, 10, 15 );

        $scan = Arr\scan(
            function ($carry, $item) {
                return $carry + $item;
            },
            0
        );

        $this->assertEquals($expected, $scan($initial));

        // Get a ruuning max value using scan.
        $max = Arr\scan(
            function ($carry, $item) {
                return max($carry, $item);
            },
            0
        );

        $data     = array( 1, 3, 4, 1, 5, 9, 2, 6, 5, 3, 5, 8, 9, 7, 9 );
        $expected = array( 0, 1, 3, 4, 4, 5, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9 );
        $this->assertEquals($expected, $max($data));
    }

    /** @testdox It should be possible to create a function which allows for calling scanr on a passed array.     */
    public function testScanR(): void
    {
        $initial  = array( 1, 2, 3 );
        $expected = array( 6, 5, 3, 0 );

        $scanR = Arr\scanR(
            function ($carry, $item) {
                return $carry + $item;
            },
            0
        );

        $this->assertEquals($expected, $scanR($initial));
    }

    /** @testdox It should be possible to create a function, pre defined to perform fold/reduce on a given array. */
    public function testFold(): void
    {
        $sumMe   = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $biggest = array( 1, 5, 6, 7, 10, 2 );

        $findSum     = Arr\fold(
            function (int $carry, int $current) {
                return $current + $carry;
            },
            0
        );
        $findBiggest = Arr\fold(
            function (int $carry, int $current) {
                return max($current, $carry);
            },
            0
        );

        $this->assertEquals(55, $findSum($sumMe));
        $this->assertEquals(10, $findBiggest($biggest));
    }

    /** @testdox  It should be possible to create a function, pre defined to perform fold/reduce on a given array in reverse order. */
    public function testFoldR(): void
    {
        $data      = array( 'a', 'b', 'c', 'd' );
        $joinArray = Arr\foldR(
            function (string $carry, string $value): string {
                return $carry . $value;
            },
            ''
        );
        $this->assertEquals('dcba', $joinArray($data));
    }

    /** @testdox It should be possible to create a function will allows doing fold/reduce with access the array key also. */
    public function testFoldKeys(): void
    {
        $data     = array(
            1 => 1,
            3 => 3,
            2 => 2,
            5 => 5,
            4 => 4,
            0 => 0,
        );
        $expected = array( 'key-1::value-1', 'key-3::value-3', 'key-2::value-2', 'key-5::value-5', 'key-4::value-4', 'key-0::value-0' );

        $foldWithKeys = Arr\foldKeys(
            function (array $carry, int $key, int $value): array {
                $carry[] = "key-{$key}::value-{$value}";
                return $carry;
            }
        );

        $this->assertEquals($expected, $foldWithKeys($data));
    }

    /** @testdox It should be possible to take n number of elements from an array using Arr\take() */
    public function testTake(): void
    {
        $data     = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $expected = array( 1, 2, 3, 4, 5 );

        $take5 = Arr\take(5);
        $this->assertEquals($expected, $take5($data));

        $take3 = Arr\take(3);
        $this->assertEquals(array( 1, 2, 3 ), $take3($data));

        $take0 = Arr\take(0);
        $this->assertEquals(array(), $take0($data));

        $takeAll = Arr\take(count($data));
        $this->assertEquals($data, $takeAll($data));
    }

    /** @testdox Passing a negative number to take should result in an InvalidArgumentException being thrown */
    public function testTakeThrowsExceptionOnNegativeNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $take = Arr\take(-1);
        $take(array());
    }

    /** @testdox It should be possible to take n number of elements from an array using Arr\takeLast() */
    public function testTakeLast(): void
    {
        $data     = array( 1, 2, 3, 4, 5, 6, 7, 8, 9 );
        $expected = array( 5, 6, 7, 8, 9 );

        $take5 = Arr\takeLast(5);
        $this->assertEquals($expected, $take5($data));

        $take3 = Arr\takeLast(3);
        $this->assertEquals(array( 7, 8, 9 ), $take3($data));

        $take0 = Arr\takeLast(0);
        $this->assertEquals(array(), $take0($data));

        $takeAll = Arr\takeLast(count($data));
        $this->assertEquals($data, $takeAll($data));
    }

    /** @testdox Passing a negative number to take should result in an InvalidArgumentException being thrown */
    public function testTakeLastThrowsExceptionOnNegativeNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $take = Arr\takeLast(-1);
        $take(array());
    }

    /** @testdox It should be possible return an array which includes all values until the callback returns true using Arr\takeUntil() */
    public function testTakeUntil(): void
    {
        $data     = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $expected = array( 1, 2, 3, 4, 5, 6, 7, 8 );

        $takeUntil = Arr\takeUntil(
            function ($value) {
                return $value > 8;
            }
        );
        $this->assertEquals($expected, $takeUntil($data));

        $takeUntil = Arr\takeUntil(
            function ($value) {
                return $value < 10;
            }
        );
        $this->assertEquals(array(), $takeUntil($data));

        $takeUntil = Arr\takeUntil(
            function ($value) {
                return $value > 100;
            }
        );
        $this->assertEquals($data, $takeUntil($data));
    }

    /** @testdox It should be possible to return an array which includes all values until the callback returns false using Arr\takeWhile() */
    public function testTakeWhile(): void
    {
        $data     = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
        $expected = array( 1, 2, 3, 4, 5, 6, 7, 8, 9 );

        $takeWhile = Arr\takeWhile(
            function ($value) {
                return $value < 10;
            }
        );
        $this->assertEquals($expected, $takeWhile($data));

        $takeWhile = Arr\takeWhile(
            function ($value) {
                return $value < 5;
            }
        );
        $this->assertEquals(array( 1, 2, 3, 4 ), $takeWhile($data));

        $takeWhile = Arr\takeWhile(
            function ($value) {
                return $value < 100;
            }
        );
        $this->assertEquals($data, $takeWhile($data));
    }

    /** @testdox It should be possible to use Map() and have access to key and value. */
    public function testMapWithKeys(): void
    {
        $data     = array(
            'a' => 'pple',
            'b' => 'anana',
            'c' => 'arrot',
        );
        $expected = array( 'apple', 'banana', 'carrot' );

        $map = Arr\mapWithKey(
            function (string $key, string $value) {
                return $key . $value;
            }
        );
        $this->assertEquals($expected, $map($data));
    }

    /** @testdox It should be possible to iterate over an array and have access to the key and value.  */
    public function testIterateWithKeys(): void
    {
        $data = array(
            'a' => 'pple',
            'b' => 'anana',
            'c' => 'arrot',
        );

        $this->expectOutputString('applebananacarrot');

        $iterate = Arr\each(
            function (string $key, string $value) {
                echo $key . $value;
            }
        );

        $iterate($data);
    }

    /** @testdox It should be possible to create a function that is populated with a filter predicate, which when used on an array will return a count of matching values. */
    public function testCountBy(): void
    {
        $data = array( 'aa', 'aa', 'bb', 'bb', 'vvvv', 'vvvv', 'vvvv' );

        $countAa = Arr\filterCount(
            function (string $value) {
                return $value === 'aa';
            }
        );

        $countBb = Arr\filterCount(
            function (string $value) {
                return $value === 'bb';
            }
        );

        $countVvvv = Arr\filterCount(
            function (string $value) {
                return $value === 'vvvv';
            }
        );

        $this->assertEquals(2, $countAa($data));
        $this->assertEquals(2, $countBb($data));
        $this->assertEquals(3, $countVvvv($data));
    }

    /** @testdox It should be possible to set public (writeable) properties of any object from indexes of an array. */
    public function testSetPublicProperties(): void
    {
        $data = array(
            'name' => 'John',
            'age'  => 30,
        );

        $object = new class () {
            public $name;
            public $age;
        };

        $objectPopulator = Arr\toObject($object);
        $populated       = $objectPopulator($data);

        $this->assertEquals('John', $populated->name);
        $this->assertEquals(30, $populated->age);

        // If no object defined, use a StdClass
        $objectPopulator = Arr\toObject();
        $populated       = $objectPopulator($data);

        $this->assertEquals('John', $populated->name);
        $this->assertEquals(30, $populated->age);
    }

    /** @testdox Attempted to use a none object as the model to cast an array to, should result in an exception being thrown */
    public function testSetPublicPropertiesThrowsExceptionWhenNotObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = array(
            'name' => 'John',
            'age'  => 30,
        );

        $objectPopulator = Arr\toObject('foo');
        $populated       = $objectPopulator($data);
    }

    /** @testdox Attempting to set a property that is private or protected should throw and exception */
    public function testSetPublicPropertiesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = array(
            'name' => 'John',
            'age'  => 30,
        );

        $object = new class () {
            public $name;
            private $age;
        };

        $objectPopulator = Arr\toObject($object);
        $populated       = $objectPopulator($data);
    }

    /** @testdox When casting an array to an object, any numerical indexes should be skipped. */
    public function testSetPublicPropertiesSkipsNumericIndexes(): void
    {
        $data = array(
            'name' => 'John',
            'age'  => 35,
            0      => 'foo',
            '1'      => 'bar',
        );

        $object = new class () {
            public $name;
            public $age;
        };

        $objectPopulator = Arr\toObject($object);
        $populated       = $objectPopulator($data);

        $this->assertEquals('John', $populated->name);
        $this->assertEquals(35, $populated->age);
    }

    /** @testdox It should be possible to create a function which uses predefined configs for casting an array to a json string. */
    public function testJsonEncode(): void
    {
        $data = array(
            'name' => 'John',
            'age'  => 30,
        );

        $jsonEncode = Arr\toJson();
        $this->assertEquals('{"name":"John","age":30}', $jsonEncode($data));

        $jsonEncode = Arr\toJson(JSON_HEX_AMP);
        $this->assertEquals('{"name":"John \u0026 Sam","age":20}', $jsonEncode(['name' => 'John & Sam', 'age' => 20]));
    }

    /** @testdox It should be possible to use rsort with predefined flags and not have the original array changed (immuteable) */
    public function testRsort(): void
    {
        $data = array( 1, 2, 3, 4, 5 );

        $rsort = Arr\rsort();
        $this->assertEquals(array( 5, 4, 3, 2, 1 ), $rsort($data));
        $this->assertEquals(array( 1, 2, 3, 4, 5 ), $data);

        $rsort = Arr\rsort(SORT_NUMERIC);
        $this->assertEquals(array( 5, 4, 3, 2, 1 ), $rsort($data));
        $this->assertEquals(array( 1, 2, 3, 4, 5 ), $data);

        // Passed as reference
        $rsort = Arr\rsort(SORT_NUMERIC);
        $foo = &$data;
        $this->assertEquals(array( 5, 4, 3, 2, 1 ), $rsort($foo));
        $this->assertEquals(array( 1, 2, 3, 4, 5 ), $data);
    }

    /** testdox It should be possible to use krsort with predefined flags and not have the original array changed */
    public function testKrsort(): void
    {
        $data = array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 );

        $krsort = Arr\krsort();
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $krsort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        $krsort = Arr\krsort(SORT_NUMERIC);
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $krsort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        // Passed as reference
        $krsort = Arr\krsort(SORT_NUMERIC);
        $foo = &$data;
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $krsort($foo));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);
    }

    /** testdox It should be possible to use arsort with predefined flags and not have the original array changed */
    public function testArsort(): void
    {
        $data = array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 );

        $arsort = Arr\arsort();
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $arsort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        $arsort = Arr\arsort(SORT_NUMERIC);
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $arsort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        // Passed as reference
        $arsort = Arr\arsort(SORT_NUMERIC);
        $foo = &$data;
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $arsort($foo));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);
    }

    /** testdox It should be possible to use asort with predefined flags and not have the original array changed */
    public function testAsort(): void
    {
        $data = array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 );

        $asort = Arr\asort();
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $asort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        $asort = Arr\asort(SORT_NUMERIC);
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $asort($data));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);

        // Passed as reference
        $asort = Arr\asort(SORT_NUMERIC);
        $foo = &$data;
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $asort($foo));
        $this->assertEquals(array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 ), $data);
    }

    /** testdox It should be possible to use natcasesort with predefined flags and not have the original array changed */
    public function testNatcasesort(): void
    {
        $data = array( 'a' => 'a', 'b' => 'B', 'c' => 'C', 'd' => 'd', 'e' => 'E' );

        $natcasesort = Arr\natcasesort();
        $this->assertEquals(array( 'a' => 'a', 'b' => 'B', 'c' => 'C', 'd' => 'd', 'e' => 'E' ), $natcasesort($data));
        $this->assertEquals(array( 'a' => 'a', 'b' => 'B', 'c' => 'C', 'd' => 'd', 'e' => 'E' ), $data);

        // Passed as reference
        $natcasesort = Arr\natcasesort();
        $foo = &$data;
        $this->assertEquals(array( 'a' => 'a', 'b' => 'B', 'c' => 'C', 'd' => 'd', 'e' => 'E' ), $natcasesort($foo));
        $this->assertEquals(array( 'a' => 'a', 'b' => 'B', 'c' => 'C', 'd' => 'd', 'e' => 'E' ), $data);
    }

    /** testdox It should be possible to use uksort with predefined flags and not have the original array changed */
    public function testUksort(): void
    {
        $data = array( 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5 );
        $sortHighToLow = function ($a, $b) {
            return $b <=> $a;
        };

        $uksort = Arr\uksort($sortHighToLow);
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $uksort($data));

        // Passed as reference
        $uksort = Arr\uksort($sortHighToLow);
        $foo = &$data;
        $this->assertEquals(array( 'e' => 5, 'd' => 4, 'c' => 3, 'b' => 2, 'a' => 1 ), $uksort($foo));
    }
}
