<?php

namespace Zenaton\Parallel;

use PHPUnit\Framework\TestCase;
use Zenaton\Engine\Engine;
use Zenaton\Interfaces\TaskInterface;

class ParallelTest extends TestCase
{
    public function testDispatchItemsGivenInConstructor()
    {
        $engine = $this->createEngineMock();
        $tasks = [
            $this->createMock(TaskInterface::class),
            $this->createMock(TaskInterface::class),
            $this->createMock(TaskInterface::class),
        ];

        $engine
            ->expects($this->once())
            ->method('dispatch')
            ->with($tasks)
        ;

        $parallel = new Parallel(...$tasks);
        $parallel->dispatch();
    }

    public function testExecuteItemsGivenInConstructor()
    {
        $engine = $this->createEngineMock();
        $tasks = [
            $this->createMock(TaskInterface::class),
            $this->createMock(TaskInterface::class),
            $this->createMock(TaskInterface::class),
        ];

        $engine
            ->expects($this->once())
            ->method('execute')
            ->with($tasks)
        ;

        $parallel = new Parallel(...$tasks);
        $parallel->execute();
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Engine
     */
    private function createEngineMock()
    {
        $engine = Engine::getInstance();
        $mockEngine = $this->createMock(Engine::class);

        $injector = function () use ($mockEngine) {
            static::$instance = $mockEngine;
        };

        $injector->call($engine);

        return $mockEngine;
    }
}
