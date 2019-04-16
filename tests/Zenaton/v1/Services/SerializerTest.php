<?php

namespace Zenaton\Services;

use PHPUnit\Framework\TestCase;

final class SerializerTest extends TestCase
{
    /** @var Serializer */
    private $serializer;

    public function setUp()
    {
        $this->serializer = new Serializer();
    }

    /**
     * @dataProvider getTestEncodeData
     */
    public function testEncode($scalar, $expected)
    {
        $encoded = $this->serializer->encode($scalar);

        static::assertSame($expected, $encoded);
    }

    public function getTestEncodeData()
    {
        $closureStringContext = 'Zenaton';

        // Null
        yield [null, '{"d":null,"s":[]}'];

        // Scalar
        yield ['Zenaton is awesome', '{"d":"Zenaton is awesome","s":[]}'];
        yield ['', '{"d":"","s":[]}'];
        yield [9000, '{"d":9000,"s":[]}'];
        yield [-9000, '{"d":-9000,"s":[]}'];
        yield [true, '{"d":true,"s":[]}'];
        yield [false, '{"d":false,"s":[]}'];
        yield [9000.123, '{"d":9000.123,"s":[]}'];

        // Simple array
        yield [[1, 2, 3], '{"a":[1,2,3],"s":[]}'];
        yield [[1, 'e'], '{"a":[1,"e"],"s":[]}'];
        yield [['hello zenaton' => 'hello', 'are you okay?' => true], '{"a":{"hello zenaton":"hello","are you okay?":true},"s":[]}'];
        yield [['hello zenaton' => 'hello', 'are you okay?' => true, 'mixing arrays !', 123], '{"a":{"hello zenaton":"hello","are you okay?":true,"0":"mixing arrays !","1":123},"s":[]}'];

        // Nested arrays
        yield [[1, 2, 3, [4, 5, 6]], '{"a":[1,2,3,[4,5,6]],"s":[]}'];
        yield [['hello', 2, true, [4, 5, [6, 7, 8], [9, 10, 11]]], '{"a":["hello",2,true,[4,5,[6,7,8],[9,10,11]]],"s":[]}'];

        // Closures
        // Here we rely on SuperClosure serialization to give us the expected result because depending on the versions of dependencies the results are not always the same
        $serializeClosure = function (\Closure $closure) {
            $serializer = new \SuperClosure\Serializer();

            return json_encode($serializer->serialize($closure));
        };

        $closure = function () {
            return 'Zenaton';
        };
        yield [
            $closure,
            '{"c":"@zenaton#0","s":['.$serializeClosure($closure).']}',
        ];

        $closure = function ($punctuation) {
            return 'Zenaton'.$punctuation;
        };
        yield [
            $closure,
            '{"c":"@zenaton#0","s":['.$serializeClosure($closure).']}',
        ];

        $closure = function ($punctuation) use ($closureStringContext) {
            return $closureStringContext.$punctuation;
        };
        yield [
            $closure,
            '{"c":"@zenaton#0","s":['.$serializeClosure($closure).']}',
        ];

        // Array containing a closure
        $closure = function () {
            return 'Zenaton';
        };
        yield [
            [$closure],
            '{"a":["@zenaton#0"],"s":['.$serializeClosure($closure).']}',
        ];

        // Array containing the same closure twice
        $closure = function () {
            return 'Zenaton';
        };
        yield [[$closure, $closure], '{"a":["@zenaton#0","@zenaton#0"],"s":['.$serializeClosure($closure).']}'];

        // Array containing a resource
        $resource = fopen(__FILE__, 'r');
        yield [[$resource], '{"a":[0],"s":[]}'];
        fclose($resource);

        // Objects
        yield [new \DateTime('2018-09-05 11:33:00'), '{"o":"@zenaton#0","s":[{"n":"DateTime","p":{"date":"2018-09-05 11:33:00.000000","timezone_type":3,"timezone":"UTC"}}]}'];

        $object = new \stdClass();
        $object->nonDeclaredProperty = 42;
        yield [$object, '{"o":"@zenaton#0","s":[{"n":"stdClass","p":{"nonDeclaredProperty":42}}]}'];

        // Objects referencing other objects
        yield [
            call_user_func(function () {
                $c1 = new C1();
                $c2 = new C2();
                $c1->c2 = $c2;

                return $c1;
            }),
            static::uglify('
            {
                "o": "@zenaton#0",
                "s": [
                    {
                        "n": "Zenaton\\\\Services\\\\C1",
                        "p": {
                            "name": "Zenaton",
                            "c2": "@zenaton#1"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C2",
                        "p": {
                            "count": 9000,
                            "c3": null
                        }
                    }
                ]
            }
            '),
        ];

        // Objects referencing other objects and having a circular reference
        yield [
            call_user_func(function () {
                $c1 = new C1();
                $c2 = new C2();
                $c1->c2 = $c2;
                $c3 = new C3();
                $c2->c3 = $c3;
                $c3->c2 = $c2;

                return $c1;
            }),
            static::uglify('
            {
                "o": "@zenaton#0",
                "s": [
                    {
                        "n": "Zenaton\\\\Services\\\\C1",
                        "p": {
                            "name": "Zenaton",
                            "c2": "@zenaton#1"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C2",
                        "p": {
                            "count": 9000,
                            "c3": "@zenaton#2"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C3",
                        "p": {
                            "zenaton": true,
                            "c2": "@zenaton#1"
                        }
                    }
                ]
            }
            '),
        ];

        // Objects inheriting other objects
        yield [new ChildClass(), '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\ChildClass","p":{"grandParentProperty":"zenaton","zenaton":"is dope","parentProperty":21,"parentConstructorDefinedProperty":22,"parentOverridableProperty":false,"childProperty":42,"childConstructorDefinedProperty":43}}]}'];

        // Objects implementing Traversable
        yield [new SimpleCollection(), '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\SimpleCollection","p":[]}]}'];

        // Objects containing a resource
        $object = new C1();
        $object->name = fopen(__FILE__, 'r');
        yield [$object, '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\C1","p":{"name":0,"c2":null}}]}'];
        fclose($object->name);

        // Objects that cannot be cloned
        $object = new CannotBeCloned();
        yield [$object, '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\CannotBeCloned","p":{"a":42}}]}'];
    }

    public function testEncodeAResourceReturnsSerializationOfIntegerZero()
    {
        $handle = fopen(__FILE__, 'r');
        if ($handle === false) {
            static::fail('Cannot open file the current file using read access.');
        }

        try {
            static::assertEquals('{"d":0,"s":[]}', $this->serializer->encode($handle));
        } finally {
            fclose($handle);
        }
    }

    /**
     * @dataProvider getTestDecodeData
     */
    public function testDecode($expectationsCallback, $encodedString)
    {
        $decoded = $this->serializer->decode($encodedString);

        $expectationsCallback($decoded);
    }

    public function getTestDecodeData()
    {
        // Null
        yield [
            function ($decoded) {
                static::assertNull($decoded);
            },
            '{"d":null,"s":[]}',
        ];

        // Scalar
        $createAssertion = function ($expected) {
            return function ($actual) use ($expected) {
                static::assertSame($expected, $actual);
            };
        };
        yield [$createAssertion('Zenaton is awesome'), '{"d":"Zenaton is awesome","s":[]}'];
        yield [$createAssertion(''), '{"d":"","s":[]}'];
        yield [$createAssertion(9000), '{"d":9000,"s":[]}'];
        yield [$createAssertion(-9000), '{"d":-9000,"s":[]}'];
        yield [$createAssertion(true), '{"d":true,"s":[]}'];
        yield [$createAssertion(false), '{"d":false,"s":[]}'];
        yield [$createAssertion(9000.123), '{"d":9000.123,"s":[]}'];

        // Simple array
        yield [$createAssertion([1, 2, 3]), '{"a":[1,2,3],"s":[]}'];
        yield [$createAssertion([1, 'e']), '{"a":[1,"e"],"s":[]}'];
        yield [$createAssertion(['hello zenaton' => 'hello', 'are you okay?' => true]), '{"a":{"hello zenaton":"hello","are you okay?":true},"s":[]}'];
        yield [$createAssertion(['hello zenaton' => 'hello', 'are you okay?' => true, 'mixing arrays !', 123]), '{"a":{"hello zenaton":"hello","are you okay?":true,"0":"mixing arrays !","1":123},"s":[]}'];

        // Nested arrays
        yield [$createAssertion([1, 2, 3, [4, 5, 6]]), '{"a":[1,2,3,[4,5,6]],"s":[]}'];
        yield [$createAssertion(['hello', 2, true, [4, 5, [6, 7, 8], [9, 10, 11]]]), '{"a":["hello",2,true,[4,5,[6,7,8],[9,10,11]]],"s":[]}'];

        // Closures
        $createAssertion = function () {
            return function ($actual) {
                static::assertInstanceOf(\Closure::class, $actual);
            };
        };
        yield [
            $createAssertion(),
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":168:{a:5:{s:4:\"code\";s:37:\"function () {\n    return \'Zenaton\';\n}\";s:7:\"context\";a:0:{}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];
        yield [
            $createAssertion(),
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":169:{a:5:{s:4:\"code\";s:38:\"function () {\n    return \'Zenaton\';\n};\";s:7:\"context\";a:0:{}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];
        yield [
            $createAssertion(),
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":195:{a:5:{s:4:\"code\";s:64:\"function ($punctuation) {\n    return \'Zenaton\' . $punctuation;\n}\";s:7:\"context\";a:0:{}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];
        yield [
            $createAssertion(),
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":196:{a:5:{s:4:\"code\";s:65:\"function ($punctuation) {\n    return \'Zenaton\' . $punctuation;\n};\";s:7:\"context\";a:0:{}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];
        yield [
            $createAssertion(),
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":277:{a:5:{s:4:\"code\";s:103:\"function ($punctuation) use($closureStringContext) {\n    return $closureStringContext . $punctuation;\n}\";s:7:\"context\";a:1:{s:20:\"closureStringContext\";s:7:\"Zenaton\";}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];
        yield [
            $createAssertion,
            '{"c":"@zenaton#0","s":["C:32:\"SuperClosure\\\\SerializableClosure\":278:{a:5:{s:4:\"code\";s:104:\"function ($punctuation) use($closureStringContext) {\n    return $closureStringContext . $punctuation;\n};\";s:7:\"context\";a:1:{s:20:\"closureStringContext\";s:7:\"Zenaton\";}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];

        // Array containing the same closure twice
        yield [
            function ($actual) {
                static::assertTrue(is_array($actual));
                static::assertCount(2, $actual);
                static::assertInstanceOf(\Closure::class, $actual[0]);
                static::assertInstanceOf(\Closure::class, $actual[1]);
                static::assertSame($actual[0], $actual[1]);
            },
            '{"a":["@zenaton#0","@zenaton#0"],"s":["C:32:\"SuperClosure\\\\SerializableClosure\":168:{a:5:{s:4:\"code\";s:37:\"function () {\n    return \'Zenaton\';\n}\";s:7:\"context\";a:0:{}s:7:\"binding\";N;s:5:\"scope\";s:31:\"Zenaton\\\\Services\\\\SerializerTest\";s:8:\"isStatic\";b:0;}}"]}',
        ];

        // Objects
        yield [function ($actual) {
            $equalDateTime = new \DateTime('2018-09-05 11:33:00');
            static::assertInstanceOf(\DateTime::class, $actual);
            static::assertEquals($equalDateTime, $actual);
        }, '{"o":"@zenaton#0","s":[{"n":"DateTime","p":{"date":"2018-09-05 11:33:00.000000","timezone_type":3,"timezone":"UTC"}}]}'];

        yield [function ($actual) {
            static::assertInstanceOf(\stdClass::class, $actual);
            static::assertEquals(42, $actual->nonDeclaredProperty);
        }, '{"o":"@zenaton#0","s":[{"n":"stdClass","p":{"nonDeclaredProperty":42}}]}'];

        // Objects referencing other objects
        yield [
            function ($actual) {
                static::assertInstanceOf(C1::class, $actual);
                static::assertAttributeSame('Zenaton', 'name', $actual);
                static::assertAttributeInstanceOf(C2::class, 'c2', $actual);
                static::assertAttributeSame(9000, 'count', $actual->c2);
                static::assertAttributeSame(null, 'c3', $actual->c2);
            },
            '
            {
                "o": "@zenaton#0",
                "s": [
                    {
                        "n": "Zenaton\\\\Services\\\\C1",
                        "p": {
                            "name": "Zenaton",
                            "c2": "@zenaton#1"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C2",
                        "p": {
                            "count": 9000,
                            "c3": null
                        }
                    }
                ]
            }
            ',
        ];

        yield [
            function ($actual) {
                static::assertInstanceOf(C1::class, $actual);
                static::assertAttributeSame('Zenaton', 'name', $actual);
                static::assertAttributeInstanceOf(C2::class, 'c2', $actual);
                static::assertAttributeSame(9000, 'count', $actual->c2);
                static::assertAttributeInstanceOf(C3::class, 'c3', $actual->c2);
                static::assertAttributeSame(true, 'zenaton', $actual->c2->c3);
                static::assertSame($actual->c2, $actual->c2->c3->c2);
            },
            '
            {
                "o": "@zenaton#0",
                "s": [
                    {
                        "n": "Zenaton\\\\Services\\\\C1",
                        "p": {
                            "name": "Zenaton",
                            "c2": "@zenaton#1"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C2",
                        "p": {
                            "count": 9000,
                            "c3": "@zenaton#2"
                        }
                    },
                    {
                        "n": "Zenaton\\\\Services\\\\C3",
                        "p": {
                            "zenaton": true,
                            "c2": "@zenaton#1"
                        }
                    }
                ]
            }
            ',
        ];

        // Objects inheriting other objects
        yield [function ($actual) {
            static::assertInstanceOf(ChildClass::class, $actual);
            static::assertAttributeSame('zenaton', 'grandParentProperty', $actual);
            static::assertAttributeSame('is dope', 'zenaton', $actual);
            static::assertAttributeSame(21, 'parentProperty', $actual);
            static::assertAttributeSame(22, 'parentConstructorDefinedProperty', $actual);
            static::assertAttributeSame(false, 'parentOverridableProperty', $actual);
            static::assertAttributeSame(42, 'childProperty', $actual);
            static::assertAttributeSame(43, 'childConstructorDefinedProperty', $actual);
        }, '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\ChildClass","p":{"grandParentProperty":"zenaton","zenaton":"is dope","parentProperty":21,"parentConstructorDefinedProperty":22,"parentOverridableProperty":false,"childProperty":42,"childConstructorDefinedProperty":43}}]}'];

        // Objects implementing Traversable
        yield [function ($actual) {
            static::assertInstanceOf(SimpleCollection::class, $actual);
            $items = [];
            foreach ($actual as $item) {
                $items[] = $item;
            }
            static::assertEquals(['z', 'e', 'n', 'a', 't', 'o', 'n'], $items);
        }, '{"o":"@zenaton#0","s":[{"n":"Zenaton\\\\Services\\\\SimpleCollection","p":[]}]}'];
    }

    public function testDecodeStringContainingWrongKeyThrowsAnException()
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->serializer->decode('{"z":"","s":[]}');
    }

    public function testDecodeMalformedJsonThrowsAnException()
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->serializer->decode('{"z":"","s":[]');
    }

    //public function testDecodedClosuresReturnsCorrectResult($closure, $expectedResult)

    private static function uglify($string)
    {
        return preg_replace('/\s+/', '', $string);
    }
}

class C1
{
    public $name = 'Zenaton';
    public $c2;
}

class C2
{
    public $count = 9000;
    public $c3;
}

class C3
{
    public $zenaton = true;
    public $c2;
}

class GrandParentClass
{
    private $grandParentProperty = 'zenaton';
    public $zenaton = 'is dope';
}

class ParentClass extends GrandParentClass
{
    private $parentProperty = 21;
    private $parentConstructorDefinedProperty;
    protected $parentOverridableProperty = true;

    public function __construct()
    {
        $this->parentConstructorDefinedProperty = 22;
    }
}

class ChildClass extends ParentClass
{
    private $childProperty = 42;
    private $childConstructorDefinedProperty;

    public function __construct()
    {
        parent::__construct();

        $this->childConstructorDefinedProperty = 43;
        $this->parentOverridableProperty = false;
    }
}

class SimpleCollection implements \IteratorAggregate
{
    public function getIterator()
    {
        return new \ArrayIterator(['z', 'e', 'n', 'a', 't', 'o', 'n']);
    }
}

class CannotBeCloned
{
    private $a = 42;

    private function __clone()
    {
    }
}
