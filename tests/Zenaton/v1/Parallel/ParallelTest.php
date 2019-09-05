<?php

namespace Zenaton\Parallel;

use PHPUnit\Framework\TestCase;
use Zenaton\Engine\Engine;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Test\SingletonTesting;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Parallel\Parallel
 */
class ParallelTest extends TestCase
{
    use SingletonTesting;

    /**
     * @covers ::dispatch
     */
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

    /**
     * @covers ::execute
     */
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
