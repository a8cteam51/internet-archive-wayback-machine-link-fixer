<?php

declare(strict_types=1);

require_once dirname(__FILE__, 3) . '/FunctionsLoader.php';

/**
 * Tests for the Object functions class.
 *
 * @since 0.2.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Numbers as Num;
use PinkCrab\FunctionConstructors\Objects as Obj;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\FunctionsLoader;

use PinkCrab\FunctionConstructors\GeneralFunctions as Func;

use function PHPUnit\Framework\throwException;

class ToArrayFixtureClassObj
{
    private $propA   = 1;
    protected $propB = 2;
    public $propC    = 3;
}

trait fooTrait
{
    public function foo()
    {
        return 'foo';
    }
}

class UsesFooTrait
{
    use fooTrait;
}

class UsesFooTraitChild extends UsesFooTrait
{
}

class classWithConstructor
{
    public $a;
    public $b;
    public $c;
    public function __construct(int $b, float $c, string $a = 'default')
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}

/**
 * Object class.
 */
class ObjectTests extends TestCase
{
    /** @testdox It should be possible to create a function that allow for the defining of a class by instance or name which can be used to check if other instances of names are the same. */
    public function testObjectInstanceOf(): void
    {
        // Create the comparisson functions.
        $instance         = new \stdClass();
        $name             = \stdClass::class;
        $isSameAsInstance = Obj\isInstanceOf($instance);
        $isSameAsName     = Obj\isInstanceOf($name);

        // Should work with classes that extend from.
        $extends = new class () extends \stdClass {
        };

        $this->assertTrue($isSameAsInstance(new \stdClass()));
        $this->assertTrue($isSameAsInstance(\stdClass::class));
        $this->assertTrue($isSameAsInstance($extends));

        $this->assertTrue($isSameAsName(new \stdClass()));
        $this->assertTrue($isSameAsName(\stdClass::class));
        $this->assertTrue($isSameAsName($extends));
    }

    /** @testdox It should be possibel to check if a calss implements an interface */
    public function testObjectImplementsInterface(): void
    {
        $classWithCount = new class () implements Countable {
            public function count(): int
            {
                return 0;
            }
        };

        $classWithoutCount = new class () {
        };

        $this->assertTrue(Obj\implementsInterface(Countable::class)($classWithCount));
        $this->assertFalse(Obj\implementsInterface(Countable::class)($classWithoutCount));
    }

    /** @testdox  */
    public function testToArray(): void
    {
        // Create the simple to array wrapper.
        $toArrray = Obj\toArray();

        // Test with valid stdClass.
        $obj        = new stdClass();
        $obj->propA = 1;
        $obj->propB = 2;
        $this->assertArrayHasKey('propA', $toArrray($obj));
        $this->assertEquals(1, $toArrray($obj)['propA']);
        $this->assertArrayHasKey('propB', $toArrray($obj));
        $this->assertEquals(2, $toArrray($obj)['propB']);

        // Test only valid public properties.
        $obj = new ToArrayFixtureClassObj();
        $this->assertArrayNotHasKey('propA', $toArrray($obj));
        $this->assertArrayNotHasKey('propB', $toArrray($obj));
        $this->assertArrayHasKey('propC', $toArrray($obj));
        $this->assertEquals(3, $toArrray($obj)['propC']);

        // Check it returns blank array if any other value passed.
        $this->assertEmpty($toArrray(false));
        $this->assertEmpty($toArrray(null));
        $this->assertEmpty($toArrray(array( 1, 2, 3, 4 )));
        $this->assertEmpty($toArrray(1));
        $this->assertEmpty($toArrray(2.5));
        $this->assertEmpty($toArrray('STRING'));
    }

    /** @testdox It should be possible to check if a class uses a trait directly using an instance. */
    public function testObjectUsesTraitWithInstance(): void
    {
        $classWithTrait = new class () {
            use fooTrait;
        };

        $classWithoutTrait = new class () {
        };

        $this->assertTrue(Obj\usesTrait(fooTrait::class)($classWithTrait));
        $this->assertFalse(Obj\usesTrait(fooTrait::class)($classWithoutTrait));
    }

    /** @testdox It should be possible to check if a class uses a trait indirectly as part of a hierarchy using an instance. */
    public function testObjectUsesTraitIndirectlyWithInstance(): void
    {
        $classWithoutTrait = new class () extends UsesFooTraitChild {};
        $this->assertTrue(Obj\usesTrait(fooTrait::class)($classWithoutTrait));
    }

    /** @testdox It should be possible to check if a class uses a trait directly using class name. */
    public function testObjectUsesTraitWithName(): void
    {
        $this->assertTrue(Obj\usesTrait(fooTrait::class)(UsesFooTrait::class));
        $this->assertFalse(Obj\usesTrait(fooTrait::class)(ToArrayFixtureClassObj::class));
    }

    /** @testdox It should be possible to check if a class uses a trait indirectly as part of a hierarchy using class name. */
    public function testObjectUsesTraitIndirectlyWithName(): void
    {
        $this->assertTrue(Obj\usesTrait(fooTrait::class)(UsesFooTraitChild::class));
    }

    /** @testdox Attempting to check if a none object or class string uses a trait should result in a InvalidArgumentException exception being thrown. */
    public function testObjectUsesTraitWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Obj\usesTrait(fooTrait::class)('Not a class');
    }

    /** @testdox It should be possible to create an instance of an object with a base set of properties.*/
    public function testObjectWithBaseProperties(): void
    {
        $objFactory = Obj\createWith(classWithConstructor::class, ['a' => 'a', 'b' => 1, 'c' => 2.5]);
        $instance = $objFactory();
        $this->assertEquals('a', $instance->a);
        $this->assertEquals(1, $instance->b);
        $this->assertEquals(2.5, $instance->c);
        $this->assertInstanceOf(classWithConstructor::class, $instance);
    }

    /** @testdox It should be possible to create an instance of an object with the array of args in any order. */
    public function testObjectWithBasePropertiesInAnyOrder(): void
    {
        $objFactory = Obj\createWith(classWithConstructor::class, ['c' => 3.5, 'b' => 2, 'a' => 'b']);
        $instance = $objFactory();
        $this->assertEquals('b', $instance->a);
        $this->assertEquals(2, $instance->b);
        $this->assertEquals(3.5, $instance->c);
        $this->assertInstanceOf(classWithConstructor::class, $instance);
    }

    /** @testdox It should be possible to create an instance of an object and have any default values taken in to account. */
    public function testObjectWithBasePropertiesAndDefaults(): void
    {
        $objFactory = Obj\createWith(classWithConstructor::class, ['b' => 1, 'c' => 2.5]);
        $instance = $objFactory();
        $this->assertEquals('default', $instance->a);
        $this->assertEquals(1, $instance->b);
        $this->assertEquals(2.5, $instance->c);
        $this->assertInstanceOf(classWithConstructor::class, $instance);
    }

    /** @testdox It should be possible to create an instance of an object with defined initial values, but allow these to be overwritten when called. */
    public function testObjectWithBasePropertiesAndOverwrite(): void
    {
        $objFactory = Obj\createWith(classWithConstructor::class);
        $instance = $objFactory(['a' => 'b', 'b' => 2, 'c' => 3.5]);
        $this->assertEquals('b', $instance->a);
        $this->assertEquals(2, $instance->b);
        $this->assertEquals(3.5, $instance->c);
        $this->assertInstanceOf(classWithConstructor::class, $instance);
    }

    /** @testdox Trying to find if an object in an instance of something that is not an object, should result in an invalid argument exception */
    public function testObjectInstanceOfWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Obj\isInstanceOf('Not an object');
    }

    /** @testdox Trying to check any type that is not an object is an instance of a class, should result in an Invalid Argument Exception */
    public function testObjectInstanceOfWithInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Obj\isInstanceOf(new stdClass())('Not a class');
    }
}
