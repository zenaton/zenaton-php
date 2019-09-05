<?php

namespace Zenaton\Parallel;

use PHPUnit\Framework\TestCase;
use Zenaton\Engine\Engine;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Test\SingletonTesting;

/**
 * @internal
 *
 * @covers \Zenaton\Parallel\Parallel
 */
class ParallelTest extends TestCase
{
    use SingletonTesting;

    public function testDispatchItemsGivenInConstructor()
    {
        $engine = $this->replaceSingletonWithMock(Engine::class);
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
        $engine = $this->replaceSingletonWithMock(Engine::class);
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
}
