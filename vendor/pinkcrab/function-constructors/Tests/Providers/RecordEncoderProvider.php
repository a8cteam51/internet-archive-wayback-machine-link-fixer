<?php

declare(strict_types=1);

namespace PinkCrab\FunctionConstructors\Tests\Providers;

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;
use PinkCrab\FunctionConstructors\GeneralFunctions as Func;

class RecordEncoderProvider
{
    public static function userObjectSimple(): array
    {
        return [
            'data' => [
                'id' => 15,
                'active' => true,
                'decimal' => 1.23,
                'name' => (object)[
                    'first' => 'Bob',
                    'last' => 'Flemming'
                ],
                'tags' => ['tag1', 'tag2', 'tag3'],
                'deepTags' => [
                    ['ref' => '13', 'records' => [ [1,4,56], [5,67,8] ] ],
                    ['ref' => '16', 'records' => [ [4,8,79], [12,39,78] ] ],
                    ['ref' => '37', 'records' => [ [123,8,3], [2,67,3], [12], [127, 854] ] ],
                ],
                'broken' => [
                    'data' => (object)[
                        'date' => time(),
                        'content' => \INF
                    ]
                ]
            ],
            'expected' => [
                'type' => 'object',
                'values' => [
                    'id' => 15,
                    'date' => 'Always now!',
                    'active' => true,
                    'name' => 'Bob Flemming',
                    'tags' => 3,
                    'brokenContent' => \INF,
                    'recordCount' => 21
                ]
            ]

        ];
    }
}
