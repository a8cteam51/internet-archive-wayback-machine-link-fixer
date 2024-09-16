<?php

namespace TestStub;

use DateTime;
use PinkCrab\FunctionConstructors\Arrays as Arr;
use PinkCrab\FunctionConstructors\Strings as Str;
use PinkCrab\FunctionConstructors\Comparisons as Comp;
use PinkCrab\FunctionConstructors\GeneralFunctions as Func;

class ComparissonCases
{
    /**
     * Returns an array of strings for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function stringComparisson(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'expected' => 'hello',
                    'test'     => 'hello',
                ),
                array(
                    'expected' => '  t',
                    'test'     => '  t',
                ),
                array(
                    'expected' => '123',
                    'test'     => '123',
                ),
                array(
                    'expected' => '     8   u    t    y',
                    'test'     => '     8   u    t    y',
                ),
            );
        } else {
            return array(
                array(
                    'expected' => 'hello',
                    'test'     => 'hi',
                ),
                array(
                    'expected' => 't  ',
                    'test'     => '  t',
                ),
                array(
                    'expected' => '123',
                    'test'     => 123,
                ),
                array(
                    'expected' => '     8   u    t    y',
                    'test'     => '8   u    t    y',
                ),
            );
        }
    }

    /**
     * Returns an array of integers for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function integerComparisons(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'expected' => 12,
                    'test'     => 12,
                ),
                array(
                    'expected' => 85, // OCTAL
                    'test'     => 00125,
                ),
                array(
                    'expected' => E_USER_NOTICE,
                    'test'     => 1024,
                ),
                array(
                    'expected' => 24,
                    'test'     => 6 * 4,
                ),
                array(
                    'expected' => 4,
                    'test'     => count(array( 1, 2, 3, 4 )),
                ),
            );
        } else {
            return array(
                array(
                    'expected' => 1,
                    'test'     => 1.1,
                ),
                array(
                    'expected' => 1 - 0,
                    'test'     => 0,
                ),
                array(
                    'expected' => 4 * 4,
                    'test'     => 44,
                ),
                array(
                    'expected' => 12 / 12,
                    'test'     => 0,
                ),
            );
        }
    }

    /**
     * Returns an array of floats for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function floatComparisons(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'expected' => sqrt(2.25),
                    'test'     => 1.5,
                ),
                array(
                    'expected' => 78.5,
                    'test'     => 78.5,
                ),
                array(
                    'expected' => (12 / 5),
                    'test'     => 2.4,
                ),
                array(
                    'expected' => 2 / M_PI,
                    'test'     => M_2_PI,
                ),
            );
        } else {
            return array(
                array(
                    'expected' => sqrt(143),
                    'test'     => 12,
                ),
                array(
                    'expected' => 78.5,
                    'test'     => 80,
                ),
                array(
                    'expected' => (12 / 5),
                    'test'     => 2.5,
                ),
                array(
                    'expected' => M_PI,
                    'test'     => 3.2,
                ),
            );
        }
    }

    /**
     * Returns an array of integers for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function arrayComparisons(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'expected' => array( 1, 2, 3, 4, 5 ),
                    'test'     => explode(',', '1,2,3,4,5'),
                ),
                array(
                    'expected' => array( 1, 2, 3, 'a', 'b', 'c' ),
                    'test'     => array_merge(
                        array( 1, 2, 3 ),
                        array( 'a', 'b', 'c' )
                    ),
                ),
                array(
                    'expected' => array( 2, 4, 6, 8, 10 ),
                    'test'     => array_map(
                        function ($e) {
                            return $e * 2;
                        },
                        array( 1, 2, 3, 4, 5 )
                    ),
                ),
                array(
                    'expected' => array( 'name' => 'Barry' ),
                    'test'     => (array) new class () {
                        public $name = 'Barry';
                    },
                ),
            );
        } else {
            return array(
                array(
                    'expected' => array( 1, 2, 3, 4, 5, 6 ),
                    'test'     => explode(',', '1,2,3,4,5'),
                ),
                array(
                    'expected' => array( 1, 2, 3, 'a', 'b', 'd' ),
                    'test'     => array_merge(
                        array( 1, 2, 3 ),
                        array( 'a', 'b', 'c' )
                    ),
                ),
                array(
                    'expected' => array( 2, 4, 6, 8, 10 ),
                    'test'     => array_map(
                        function ($e) {
                            return $e * 3;
                        },
                        array( 1, 2, 3, 4, 5 )
                    ),
                ),
                array(
                    'expected' => array( 'name' => 'Barry' ),
                    'test'     => (array) new class () {
                        public $name = 'Jane';
                    },
                ),
            );
        }
    }

    /**
     * Returns an object of integers for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function objectComparisons(string $type): array
    {
        if ($type === 'pass') {
            $a = new class () {
                public $name = 'I WAS A VAR';
            };
            $b = new class () {
                public $name = 'I WAS A VAR';
            };

            return array(
                array(
                    'expected' => $a,
                    'test'     => $b,
                ),
                array(
                    'expected' => new class () {
                        public $name = 'Barry';
                    },
                    'test'     => (object) array( 'name' => 'Barry' ),
                ),
                array(
                    'expected' => (object) array(
                        'a' => 'a',
                        'b' => 'b',
                    ),
                    'test'     => new class () {
                        public $a = 'a';
                        public $b = 'b';
                    },
                ),
                array(
                    'expected' => new \stdClass(),
                    'test'     => (object) array(),
                ),
                array(
                    'expected' => json_decode(json_encode(array( 'itWas' => 'anArray' ))),
                    'test'     => (object) array( 'itWas' => 'anArray' ),
                ),
            );
        } else {
            return array(
                array(
                    'expected' => new class () {
                        public $name = 'Barry';
                    },
                    'test'     => (object) array( 'name' => 'Daisy' ),
                ),
                array(
                    'expected' => (object) array(
                        'a' => 'b',
                        'b' => 'a',
                    ),
                    'test'     => new class () {
                        public $a = 'a';
                        public $b = 'b';
                    },
                ),
                array(
                    'expected' => (object) array( 'unlucky' => 'mate' ),
                    'test'     => new \stdClass(),
                ),
                array(
                    'expected' => json_decode(
                        json_encode(
                            array(
                                'itWas'  => 'anArray',
                                'really' => false,
                            )
                        )
                    ),
                    'test'     => (object) array( 'itWas' => 'anArray' ),
                ),
            );
        }
    }

    /**
     * Returns an object of integers for comparisson tests.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function equalToOrComparisson(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'needles'  => array(
                        new class () {
                            public $name = 'Barry';
                        },
                        new class () {
                            public $name = 'Jane';
                        },
                    ),
                    'haystack' => (object) array( 'name' => 'Barry' ),
                ),
                array(
                    'needles'  => array(
                        new class () {
                            public $name = 'Barry';
                            public $points = 46587;
                        },
                        new class () {
                            public $name = 'Jane';
                            public $points = 132645;
                        },
                    ),
                    'haystack' => new class () {
                        public $name = 'Barry';
                        public $points = 46587;
                    },
                ),
                array(
                    'needles'  => array( 1, 2, 3, 4, 6 ),
                    'haystack' => 4,
                ),
                array(
                    'needles'  => array( 'a', 'b', 'c', 'd' ),
                    'haystack' => 'c',
                ),
                array(
                    'needles'  => array( array( 'a' ), array( 'b' ), 'c', array( 'd' ) ),
                    'haystack' => array( 'a' ),
                ),

            );
        } else {
            return array(
                array(
                    'needles'  => array(
                        new class () {
                            public $name = 'Barry';
                        },
                        new class () {
                            public $name = 'Jane';
                        },
                    ),
                    'haystack' => (object) array( 'name' => 'Rebbeca' ),
                ),
                array(
                    'needles'  => array(
                        new class () {
                            public $name = 'Barry';
                            public $points = 46587;
                        },
                        new class () {
                            public $name = 'Jane';
                            public $points = 132645;
                        },
                    ),
                    'haystack' => new class () {
                        public $name = 'Barry';
                        public $points = 88888;
                    },
                ),
                array(
                    'needles'  => array( 1, 2, 3, 4, 6 ),
                    'haystack' => 41,
                ),
                array(
                    'needles'  => array( 'a', 'b', 'c', 'd' ),
                    'haystack' => 'R',
                ),
                array(
                    'needles'  => array( array( 'a' ), array( 'b' ), 'c', array( 'd' ) ),
                    'haystack' => array( 'a', 'B' ),
                ),
            );
        }
    }

    /**
     * Returns an array of mixed scalar types.
     * Using 'pass' as the type will produce matching cases, else failues.
     *
     * @param string $type
     * @return array
     */
    public static function scalarComparisons(string $type): array
    {
        if ($type === 'pass') {
            return array(
                array( // String
                    'dfsdfs',
                    'dfdsfsdfs',
                    '          _    ',
                    '123',
                    '£$%',
                    '!',
                    "\r",
                ),
                array( // Int
                    123,
                    0015,
                    (12 * 12),
                    __LINE__,
                    PHP_INT_MIN,
                    E_USER_NOTICE,
                    hexdec('ee'),
                    bindec('110011'),
                ),
                array( // FLoat
                    sqrt(143),
                    78.5,
                    (12 / 5),
                    M_PI,

                ),
                array( // Srray
                    array( 'xx' ),
                    array( true, false, null ),
                    explode(',', 'this,is,a,test,string'),
                    array( array( 1, array( 1, 5, 6, 7, array( 44, array( 5 ) ) ) ) ),
                ),
                array( // Object
                    new class () {
                    },
                    json_decode(json_encode(array( 'itWas' => 'anArray' ))),
                    new DateTime('2020-01-01T15:03:01.012345Z'),
                    dir(__DIR__),
                    new \stdClass(),
                    (object) array( 'iWasAlso' => 'anArray' ),
                ),
            );
        } else {
            return array(
                array( // Mixed madness
                    'dfsdfs',
                    'dfdsfsdfs',
                    '          _    ',
                    123,
                    (12 * 12),
                    __LINE__,
                    PHP_INT_MIN,
                    json_decode(json_encode(array( 'itWas' => 'anArray' ))),
                    new DateTime('2020-01-01T15:03:01.012345Z'),
                    E_USER_NOTICE,
                    sqrt(144),
                    (12 / 5),
                    M_PI,
                    array( true, false, null ),
                    explode(',', 'this,is,a,test,string'),
                    hexdec('ee'),
                    bindec('110011'),
                ),
                array( // Array and casted arrays.
                    array( 'xx' ),
                    explode(',', 'this,is,a,test,string'),
                    (object) array( 'iWasAlso' => 'anArray' ),
                ),
                array( // Int, FLoat, String
                    12,
                    12.1,
                    '12',
                ),
            );
        }
    }

    public static function groupSingleAndComparisonsArrays($type = 'fail'): array
    {
        if ($type === 'pass') {
            return array(
                array(
                    'array'    => array(
                        'HI john hello',
                        'HI mark hello',
                        'HI claire hello',
                    ),
                    'expected' => array( 'HI john hello' ),
                    'function' => Arr\filterAnd(
                        Str\contains('john'),
                        Str\startsWith('HI')
                    ),
                ),

                // Array contents
                array(
                    'array'    => array(
                        array(
                            'a' => 'alpha',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        (object) array(
                            'a' => 'delta',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        array(
                            'a' => 'bravo',
                            'b' => 'd',
                            'c' => 'e',
                        ),

                        array(
                            'a' => 'charlie',
                            'b' => 'b',
                            'c' => 'e',
                        ),
                    ),
                    'expected' => array(
                        array(
                            'a' => 'alpha',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        (object) array(
                            'a' => 'delta',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                    ),
                    'function' => Arr\filterAnd(
                        Func\compose(Func\getProperty('b'), Comp\isEqualTo('b')),
                        Func\compose(Func\getProperty('c'), Comp\isEqualTo('c'))
                    ),
                ),

            );
        } else {
            return array(
                array(
                    'array'    => array(
                        'HI john hello',
                        'HI mark hello',
                        'HI claire hello',
                    ),
                    'expected' => array( 'HI john hello' ),
                    'function' => Arr\filterAnd(
                        Str\contains('John'),
                        Str\startsWith('HI')
                    ),
                ),

                // Array contents
                array(
                    'array'    => array(
                        array(
                            'a' => 'alpha',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        (object) array(
                            'a' => 'delta',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        array(
                            'a' => 'bravo',
                            'b' => 'd',
                            'c' => 'e',
                        ),

                        array(
                            'a' => 'charlie',
                            'b' => 'b',
                            'c' => 'e',
                        ),
                    ),
                    'expected' => array(
                        array(
                            'a' => 'alpha',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                        (object) array(
                            'a' => 'delta',
                            'b' => 'b',
                            'c' => 'c',
                        ),
                    ),
                    'function' => Arr\filterAnd(
                        Func\compose(Func\getProperty('b'), Comp\isEqualTo('b')),
                        Func\compose(Func\getProperty('c'), Comp\isEqualTo('cAt'))
                    ),
                ),

            );
        }
    }

    public static function groupSingleAndComparisonsStrings(string $type): array
    {
        // Functions.
        $appleWithDashes = Comp\groupAnd(
            Str\contains('APPLE'),
            Str\startsWith('--')
        );
        $randomStrings   = Comp\groupAnd(
            Str\contains('ffff'),
            Str\contains('trt'),
            Str\startsWith('dg')
        );
        $numbersInString = Comp\groupAnd(
            Str\contains('666'),
            Str\contains('333')
        );
        if ($type === 'pass') {
            return array(
                array(
                    'value'    => array(
                        '--APPLE',
                        '--hjkgjhgAPPLE',
                        '--------APPLE',
                        '--------AAAAAAAAAAPPLE',
                    ),
                    'function' => $appleWithDashes,
                ),
                array(
                    'value'    => array(
                        'dgdsgsdgsdgsdtrtdghgfdsffffgd',
                        'dgjjkdffffshgjktrtdhgjksdhgjksdhk',
                    ),
                    'function' => $randomStrings,
                ),
                array(
                    'value'    => array(
                        'dsf333sdfsd666fgdfdfgdf',
                        'dgdf333sddasda666sdas',
                    ),
                    'function' => $numbersInString,
                ),
            );
        } else {
            return array(
                array(
                    'value'    => array(
                        '--PEAR',
                        '__hjkgjhgAPPLE',
                        '--------APPEARLE',
                        '--------AAAAAAAAAAPPLPEAR',
                    ),
                    'function' => $appleWithDashes,
                ),
                array(
                    'value'    => array(
                        'iuoiuouiouio',
                        '.kkj.ik.kjk.jk.jkjk.jk',
                    ),
                    'function' => $randomStrings,
                ),
                array(
                    'value'    => array(
                        'dsfsdfsd555fgdfdfgdf',
                        'dgdf332sddasdasdas',
                    ),
                    'function' => $numbersInString,
                ),
            );
        }
    }

    public static function groupedAndOrComparissonMixed(string $type): array
    {
        $orAndOr1  = Comp\groupAnd(
            Comp\groupOr(
                Str\contains('666'),
                Str\contains('333')
            ),
            Comp\groupOr(
                Str\startsWith('dg'),
                Str\startsWith('ff')
            )
        );
        $andOrAnd1 = Comp\groupOr(
            Comp\groupAnd(
                Str\startsWith('dg'),
                Str\contains('666')
            ),
            Comp\groupAnd(
                Str\contains('333'),
                Str\startsWith('ff')
            )
        );

        if ($type === 'pass') {
            return array(
                array(
                    'value'    => array(
                        'dg666_____jkhjkhkjhk',
                        'dg333_____jkhjkhkjhk',
                        'ff666_____jkhjkhkjhk',
                        'ff333_____jkhjkhkjhk',
                        'dg_hjjkhkj_uyuiyiuyiu666_____jkhjkhkjhk',
                        'dg_hjjkhkj_uyuiyiuyiu333_____jkhjkhkjhk',
                        'ff_hjjkhkj_uyuiyiuyiu666_____jkhjkhkjhk',
                        'ff_hjjkhkj_uyuiyiuyiu333_____jkhjkhkjhk',
                    ),
                    'function' => $orAndOr1,
                ),
                array(
                    'value'    => array(
                        'dg666_____jkhjkhkjhk',
                        'ff333_____jkhjkhkjhk',
                        'dg_hjjkhkj_uyuiyiuyiu666_____jkhjkhkjhk',
                        'ff_hjjkhkj_uyuiyiuyiu333_____jkhjkhkjhk',
                    ),
                    'function' => $andOrAnd1,
                ),
            );
        } else {
            return array(
                array(
                    'value'    => array(
                        '555tt_____jkhjkhkjhk',
                        '333tt_____jkhjkhkjhk',
                        '555ff_____jkhjkhkjhk',
                        '333ff_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu555tt_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu333tt_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu555ff_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu333ff_____jkhjkhkjhk',
                    ),
                    'function' => $orAndOr1,
                ),
                array(
                    'value'    => array(
                        '666ff_____jkhjkhkjhk',
                        '333dg_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu666ff_____jkhjkhkjhk',
                        '_hjjkhkj_uyuiyiuyiu333dg_____jkhjkhkjhk',
                    ),
                    'function' => $andOrAnd1,
                ),
            );
        }
    }
}
