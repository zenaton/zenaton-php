<?php

namespace Zenaton\Traits;

use PHPUnit\Framework\TestCase;

final class IsImplementationOfTraitTest extends TestCase
{
    private $IsImplementationOfMock;

    public function setUp()
    {
        $this->IsImplementationOfMock = new IsImplementationOfMock();
    }

    public function testIsImplementationOfReturnsTrueWhenAClassImplementsAnInterface()
    {
        static::assertTrue($this->IsImplementationOfMock->classImplements(\ArrayIterator::class, \Iterator::class));
    }

    public function testIsImplementationOfReturnsFalseWhenAClassDoesNotImplementAnInterface()
    {
        static::assertFalse($this->IsImplementationOfMock->classImplements(\ArrayIterator::class, \OuterIterator::class));
    }

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
