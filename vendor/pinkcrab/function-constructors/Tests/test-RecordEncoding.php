<?php

declare(strict_types=1);

namespace PinkCrab\FunctionConstructors\Tests;

use stdClass;
use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Objects as O;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\GeneralFunctions as F;
use PinkCrab\FunctionConstructors\Tests\Providers\RecordEncoderProvider;

class RecordEncoderTests extends TestCase
{
    /** @test */
    public function objectFromArraySingle()
    {
        $testData = RecordEncoderProvider::userObjectSimple();
        $newObject = F\recordEncoder(new stdClass());
        $encoder = [
            F\encodeProperty('id', F\pluckProperty('id')),
            F\encodeProperty('date', F\always('Always now!')),
            F\encodeProperty('active', F\pluckProperty('active')),
            F\encodeProperty('name', F\composeR(
                Arr\toString(' '),
                O\toArray(),
                F\pluckProperty('name')
            )),
            F\encodeProperty('tags', F\composeR('count', F\pluckProperty('tags'))),
            F\encodeProperty('brokenContent', F\pluckProperty('broken', 'data', 'content')),
            F\encodeProperty('recordCount', F\composeR(
                'count',
                Arr\flattenByN(2),
                Arr\column('records'),
                F\pluckProperty('deepTags')
            )),
        ];

        $userEncoder = $newObject(...$encoder);
        $userBob = $userEncoder($testData['data']);

        $expected = $testData['expected'];

        $this->assertEquals($expected['type'], gettype($userBob));
        $this->assertEquals($expected['values']['id'], $userBob->id);
        $this->assertEquals($expected['values']['date'], $userBob->date);
        $this->assertEquals($expected['values']['active'], $userBob->active);
        $this->assertEquals($expected['values']['name'], $userBob->name);
        $this->assertEquals($expected['values']['brokenContent'], $userBob->brokenContent);
        $this->assertEquals($expected['values']['recordCount'], $userBob->recordCount);
    }
}
