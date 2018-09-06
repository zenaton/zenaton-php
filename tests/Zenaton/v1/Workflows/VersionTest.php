<?php

namespace Zenaton\Workflows;

use PHPUnit\Framework\TestCase;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Exceptions\ExternalZenatonException;

final class VersionTest extends TestCase
{
    /**
     * @dataProvider getTestGetCurrentImplementationReturnsAnInstanceData
     */
    public function testGetCurrentImplementationReturnsAnInstance($versions)
    {
        $workflow = $this->getMockForAbstractClass(Version::class);

        $workflow
            ->expects($this->any())
            ->method('versions')
            ->willReturn($versions)
        ;

        $instance = $workflow->getCurrentImplementation();

        static::assertInstanceOf(WorkflowV2::class, $instance);
    }

    public function getTestGetCurrentImplementationReturnsAnInstanceData()
    {
        yield [
            [WorkflowV2::class],
        ];

        yield [
            [
                WorkflowV1::class,
                WorkflowV2::class,
            ],
        ];
    }

    /**
     * @dataProvider getTestGetCurrentImplementationThrowsAnExceptionWhenInvalidVersionsData
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
        // The closure is bound to the MockWorkflow class when executed ($this = MockWorkflow instance)
        $assertions = function () {
            assertInstanceOf(MockWorkflow::class, $this);
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
                WorkflowV1::class,
                WorkflowV2::class,
                MockWorkflow::class,
            ])
        ;

        $workflow->handle();
    }
}

final class WorkflowV1 implements WorkflowInterface
{
    public function handle()
    {
        return;
    }
}

final class WorkflowV2 implements WorkflowInterface
{
    public function handle()
    {
        return;
    }
}

final class MockWorkflow implements WorkflowInterface
{
    public function __construct(\Closure $assertions)
    {
        $this->assertions = $assertions;
    }

    public function handle()
    {
        $this->assertions->call($this);
    }
}
