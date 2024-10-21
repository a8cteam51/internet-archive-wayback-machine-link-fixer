<?php

declare(strict_types=1);

require_once dirname(__FILE__, 2) . '/FunctionsLoader.php';

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;

/**
 * Tests for Examples used in docs (wiki, readme etc)
 */
class DocsExampleTest extends TestCase
{
    public function setup(): void
    {
        FunctionsLoader::include();
    }

    /**
     * Determine if two associative arrays are similar
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param mixed[] $a
     * @param mixed[] $b
     * @return bool
     */
    private function arrays_are_similar(array $a, array $b): bool
    {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // we have identical indexes, and no unequal values
        return true;
    }


    /** @testdox README : Pipe Example -- Remove all odd numbers, sort in an acceding order and double the value. */
    public function testReadmePipeArrayOfInts(): void
    {
        $data = array(0, 3, 4, 5, 6, 8, 4, 6, 8, 1, 3, 4);

        // Remove all odd numbers, sort in an acceding order and double the value.
        $newData = F\pipe(
            $data,
            Arr\filter(Num\isMultipleOf(2)), // Remove odd numbers
            Arr\natsort(),                // Sort the remaining values
            Arr\map(Num\multiply(2))      // Double the values.
        );

        $expected = array(
            2  => 8,
            6  => 8,
            11 => 8,
            4  => 12,
            7  => 12,
            5  => 16,
            8  => 16,
        );

        $this->assertSame($expected, $newData);
    }

    public function testReadmeComposeString(): void
    {
        $function = F\compose(
            F\pluckProperty('details', 'description'),
            'trim',
            Str\slice(0, 20),
            'ucwords',
            Str\append('...')
        );

        $data = array(
            array('details' => array('description' => '   This is some description   ')),
            array('details' => array('description' => '    This is some other description     ')),
        );

        $expected = array(
            'This Is Some Descrip...',
            'This Is Some Other D...',
        );

        $this->assertSame($expected, array_map($function, $data));
    }

    public function testReadingRecordProperties()
    {
        $data = array(
            array(
                'id'       => 1,
                'name'     => 'James',
                'timezone' => '+1',
                'colour'   => 'red',
            ),
            array(
                'id'       => 2,
                'name'     => 'Sam',
                'timezone' => '+1',
                'colour'   => 'red',
                'special'  => true,
            ),
            array(
                'id'       => 3,
                'name'     => 'Sarah',
                'timezone' => '+2',
                'colour'   => 'green',
            ),
            array(
                'id'       => 4,
                'name'     => 'Donna',
                'timezone' => '+2',
                'colour'   => 'blue',
                'special'  => true,
            ),
        );

        // Get all users with +2 timezone.
        $zonePlus2 = array_filter($data, F\propertyEquals('timezone', '+2'));
        $this->assertCount(2, $zonePlus2);
        $this->assertEquals(array(3, 4), array_column($zonePlus2, 'id'));

        // Get all users who have special index.
        $special = array_filter($data, F\hasProperty('special'));
        $this->assertCount(2, $zonePlus2);
        $this->assertEquals(array(2, 4), array_column($special, 'id'));

        $colours = array_map(F\getProperty('colour'), $data);
        $results = array('red', 'red', 'green', 'blue'); // Expected results.
        $this->assertEquals($results, $colours);
    }

    /** @testdox README : Property Writing Example -- Set a property of class and index of array using a custom setter function. */
    public function testWritingProperty(): void
    {
        // Set object property.
        $object         = new class () {
            public $key = 'default';
        };
        $setKeyOfObject = F\setProperty($object, 'key');
        $object         = $setKeyOfObject('new value');
        $this->assertEquals('new value', $object->key);

        // Set array index.
        $array          = array('key' => 'default');
        $setKeyOfSArray = F\setProperty($array, 'key');
        $array          = $setKeyOfSArray('new value');
        $this->assertEquals('new value', $array['key']);
    }

    /** @testdox README : Sub Strings -- Count the chars used in a string */
    public function testCharCount(): void
    {
        $charCount = Str\countChars();
        $results   = $charCount('Hello World');
        // Map the keys using chr()
        $expected = array(
            'H' => 1,
            'e' => 1,
            'l' => 3,
            'o' => 2,
            ' ' => 1,
            'W' => 1,
            'r' => 1,
            'd' => 1,
        );

        $this->assertTrue(
            $this->arrays_are_similar(
                $expected,
                Arr\mapKey('chr')($results)
            )
        );
    }

    /** @testdox README : Sub Strings -- Count the chars used in a string */
    public function testFirstFoo(): void
    {
        $firstFoo = Str\firstPosition('foo');
        $result = $firstFoo('abcdefoog');
        $this->assertEquals(5, $result);
    }

    /** @testdox README : Use takeWhile() and takeUntil() with games data */
    public function testTakeWhileTakeUntil(): void
    {
        $games = [
            ['id' => 1, 'result' => 'loss'],
            ['id' => 2, 'result' => 'loss'],
            ['id' => 3, 'result' => 'win'],
            ['id' => 4, 'result' => 'win'],
            ['id' => 5, 'result' => 'loss'],
        ];

        // All the games until the first win using takeWhile
        $initialLoosingStreak = Arr\takeWhile(
            F\propertyEquals('result', 'loss')
        );
        $this->assertEquals(
            [['id' => 1, 'result' => 'loss'], ['id' => 2, 'result' => 'loss']],
            $initialLoosingStreak($games)
        );

        // All the games until the first win using takeUntil
        $untilFirstWin = Arr\takeUntil(
            F\propertyEquals('result', 'win')
        );
        $this->assertEquals(
            [['id' => 1, 'result' => 'loss'], ['id' => 2, 'result' => 'loss']],
            $untilFirstWin($games)
        );
    }

    /** @testdox README : Use fold and scan with payments */
    public function testArrayFoldAndScanWithPayments(): void
    {
        $payments = [
            ['type' => 'card', 'amount' => 12.53],
            ['type' => 'cash', 'amount' => 21.95],
            ['type' => 'card', 'amount' => 1.99],
            ['type' => 'cash', 'amount' => 4.50],
            ['type' => 'cash', 'amount' => 21.50],
        ];

        $allCash = Arr\fold(function ($total, $payment) {
            if ($payment['type'] === 'cash') {
                $total += $payment['amount'];
            }
            return $total;
        }, 0.00);

        $this->assertEquals(47.95, $allCash($payments));

        $runningTotal = Arr\scan(function ($runningTotal, $payment) {
            $runningTotal += $payment['amount'];
            return $runningTotal;
        }, 0.00);
        $expected = [0.0, 12.53, 34.48, 36.47, 40.97, 62.47];

        $this->assertEquals($expected, $runningTotal($payments));
    }

    /** @testdox README : use groupBy and Partition*/
    public function testGroupByAndPartition(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'age' => 20, 'someMetric' => 'A12'],
            ['id' => 2, 'name' => 'Jane', 'age' => 21, 'someMetric' => 'B10'],
            ['id' => 3, 'name' => 'Joe', 'age' => 20, 'someMetric' => 'C15'],
            ['id' => 4, 'name' => 'Jack', 'age' => 18, 'someMetric' => 'B10'],
            ['id' => 5, 'name' => 'Jill', 'age' => 22, 'someMetric' => 'A12'],
        ];


        // Group by a property
        $groupedByMetric = Arr\groupBy(function ($item) {
            return $item['someMetric'];
        });

        $expected = [
            "A12" =>  [
                [
                    "id" => 1,
                    "name" => "John",
                    "age" => 20,
                    "someMetric" => "A12"
                ],
                [
                    "id" => 5,
                    "name" => "Jill",
                    "age" => 22,
                    "someMetric" => "A12"
                ]
            ],
            "B10" =>  [
                [
                    "id" => 2,
                    "name" => "Jane",
                    "age" => 21,
                    "someMetric" => "B10"
                ],
                [
                    "id" => 4,
                    "name" => "Jack",
                    "age" => 18,
                    "someMetric" => "B10"
                ]
            ],
            "C15" =>  [
                [
                    "id" => 3,
                    "name" => "Joe",
                    "age" => 20,
                    "someMetric" => "C15",
                ]
            ]
        ];

        $this->assertSame($expected, $groupedByMetric($data));

        // Partition using a predicate function.
        $over21 = Arr\partition(function ($item) {
            return $item['age'] >= 21;
        });

        $expected = [
            0 => [
                [
                    "id" => 1,
                    "name" => "John",
                    "age" => 20,
                    "someMetric" => "A12"
                ],
                [
                    "id" => 3,
                    "name" => "Joe",
                    "age" => 20,
                    "someMetric" => "C15"
                ],
                [
                    "id" => 4,
                    "name" => "Jack",
                    "age" => 18,
                    "someMetric" => "B10"
                ]
            ],
            1 => [
                [
                    "id" => 2,
                    "name" => "Jane",
                    "age" => 21,
                    "someMetric" => "B10"
                ],
                [
                    "id" => 5,
                    "name" => "Jill",
                    "age" => 22,
                    "someMetric" => "A12"
                ]
            ]
        ];

        $this->assertSame($expected, $over21($data));
    }

    /** @testdox README : use sort, ksort and uasort */
    public function testArraySort(): void
    {
        $dataWords = ['Zoo', 'cat', 'Dog', 'ant', 'bat', 'Cow'];
        $sortWords = Arr\sort(SORT_STRING | SORT_FLAG_CASE);

        $expected = ['ant', 'bat', 'cat', 'Cow', 'Dog', 'Zoo'];
        $this->assertSame($expected, $sortWords($dataWords));


        $dataBooks = [
            'ehJF89' => ['id' => 'ehjf89', 'title' => 'Some title', 'author' => 'Adam James'],
            'Retg23' => ['id' => 'retg23', 'title' => 'A Title', 'author' => 'Jane Jones'],
            'fvbI43' => ['id' => 'fvbi43', 'title' => 'Some title words', 'author' => 'Sam Smith'],
            'MggEd3' => ['id' => 'mgged3', 'title' => 'Book', 'author' => 'Will Adams'],
        ];

        // Sort the books by key
        $sortBookByKey = Arr\ksort(SORT_STRING | SORT_FLAG_CASE);
        $expected = [
            'ehJF89' => ['id' => 'ehjf89', 'title' => 'Some title', 'author' => 'Adam James'],
            'fvbI43' => ['id' => 'fvbi43', 'title' => 'Some title words', 'author' => 'Sam Smith'],
            'MggEd3' => ['id' => 'mgged3', 'title' => 'Book', 'author' => 'Will Adams'],
            'Retg23' => ['id' => 'retg23', 'title' => 'A Title', 'author' => 'Jane Jones'],
        ];
        $this->assertSame($expected, $sortBookByKey($dataBooks));

        // Sort by author
        $sortBookByAuthor = Arr\uasort(function ($a, $b) {
            return strcmp($a['author'], $b['author']);
        });
        $expected = [
            'ehJF89' => ['id' => 'ehjf89', 'title' => 'Some title', 'author' => 'Adam James'],
            'Retg23' => ['id' => 'retg23', 'title' => 'A Title', 'author' => 'Jane Jones'],
            'fvbI43' => ['id' => 'fvbi43', 'title' => 'Some title words', 'author' => 'Sam Smith'],
            'MggEd3' => ['id' => 'mgged3', 'title' => 'Book', 'author' => 'Will Adams'],
        ];
        $this->assertSame($expected, $sortBookByAuthor($dataBooks));
    }
}
