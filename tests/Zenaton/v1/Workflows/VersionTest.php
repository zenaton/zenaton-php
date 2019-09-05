<?php

namespace Zenaton\Workflows;

use PHPUnit\Framework\TestCase;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Test\Mock\Workflow\ExecutingClosureWorkflow;
use Zenaton\Test\Mock\Workflow\NullWorkflow;

/**
 * @internal
 *
 * @covers \Zenaton\Workflows\Version
 */
final class VersionTest extends TestCase
{
    /**
     * @dataProvider getTestGetCurrentImplementationReturnsAnInstanceData
     *
     * @param mixed $versions
     * @param mixed $expected
     */
    public function testGetCurrentImplementationReturnsAnInstance($versions, $expected)
    {
        $workflow = $this->getMockForAbstractClass(Version::class);

        $workflow
            ->expects($this->any())
            ->method('versions')
            ->willReturn($versions)
        ;

        $instance = $workflow->getCurrentImplementation();

        static::assertInstanceOf($expected, $instance);
    }

    public function getTestGetCurrentImplementationReturnsAnInstanceData()
    {
        yield [
            [NullWorkflow::class],
            NullWorkflow::class,
        ];

        yield [
            [
                ExecutingClosureWorkflow::class,
                NullWorkflow::class,
            ],
            NullWorkflow::class,
        ];
    }

    /**
     * @dataProvider getTestGetCurrentImplementationThrowsAnExceptionWhenInvalidVersionsData
     *
     * @param mixed $versions
     */
    public function testGetCurrentImplementationThrowsAnExceptionWhenInvalidVersions($versions)
    {
        $this->expectException(ExternalZenatonException::class);

        $workflow = $this->getMockForAbstractClass(Version::class);

        $workflow
            ->expects($this->any())
            ->method('versions')
            ->willReturn($versions)
        ;

        $workflow->getCurrentImplementation();
    }

    public function getTestGetCurrentImplementationThrowsAnExceptionWhenInvalidVersionsData()
    {
        yield [[]];
        yield [0];
        yield [null];
        yield ['Zenaton'];
    }

    public function testHandleExecutesHandleOfCurrentVersion()
    {
        $assertions = function () {
            assertInstanceOf(ExecutingClosureWorkflow::class, $this);
        };

        $workflow = $this
            ->getMockBuilder(Version::class)
            ->setConstructorArgs([$assertions])
            ->setMethods(['versions'])
            ->getMock()
        ;

        $workflow
            ->expects($this->any())
            ->method('versions')
            ->willReturn([
                NullWorkflow::class,
                ExecutingClosureWorkflow::class,
            ])
        ;

        $workflow->handle();
    }
}
