<?php

namespace Zenaton\Traits;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Traits\IsImplementationOfTrait
 */
final class IsImplementationOfTraitTest extends TestCase
{
    private $IsImplementationOfMock;

    public function setUp()
    {
        $this->IsImplementationOfMock = new IsImplementationOfMock();
    }

    /**
     * @covers ::isImplementationOf
     */
    public function testIsImplementationOfReturnsTrueWhenAClassImplementsAnInterface()
    {
        static::assertTrue($this->IsImplementationOfMock->classImplements(\ArrayIterator::class, \Iterator::class));
    }

    /**
     * @covers ::isImplementationOf
     */
    public function testIsImplementationOfReturnsFalseWhenAClassDoesNotImplementAnInterface()
    {
        static::assertFalse($this->IsImplementationOfMock->classImplements(\ArrayIterator::class, \OuterIterator::class));
    }

    /**
     * @covers ::isImplementationOf
     */
    public function testIsImplementationOfReturnsFalseWhenNotGivenAString()
    {
        static::assertFalse($this->IsImplementationOfMock->classImplements(123, \Countable::class));
    }
}

class IsImplementationOfMock
{
    use IsImplementationOfTrait;

    public function classImplements($class, $interface)
    {
        return $this->isImplementationOf($class, $interface);
    }
}
