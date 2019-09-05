<?php

namespace Zenaton\Engine;

use PHPUnit\Framework\TestCase;
use Zenaton\Client;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Test\Mock\Processor\NullProcessor;
use Zenaton\Test\SingletonTesting;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Engine\Engine
 */
class EngineTest extends TestCase
{
    use SingletonTesting;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::destroySingleton(Engine::class);
    }

    public function tearDown()
    {
        static::destroySingleton(Engine::class);

        parent::tearDown();
    }

    /**
     * @covers ::execute
     */
    public function testExecuteCallsHandleMethodOfJobsWhenProcessorIsNotSet()
    {
        $jobs = [];

        $workflowJob = $this->createMock(WorkflowInterface::class);
        $workflowJob
            ->expects($this->once())
            ->method('handle')
        ;
        $jobs[] = $workflowJob;

        $taskJob = $this->createMock(TaskInterface::class);
        $taskJob
            ->expects($this->once())
            ->method('handle')
        ;
        $jobs[] = $taskJob;

        $engine = Engine::getInstance();
        $engine->execute($jobs);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteUsesProcessorWhenItIsSet()
    {
        $task = $this->createMock(TaskInterface::class);
        $task
            ->expects($this->never())
            ->method('handle')
        ;

        $engine = Engine::getInstance();
        $engine->setProcessor(new NullProcessor());
        $engine->execute([$task]);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteJobNotWorkflowOrTaskThrowsAnException()
    {
        $this->expectException(InvalidArgumentException::class);

        $job = new \DateTime();

        $engine = Engine::getInstance();
        $engine->execute([$job]);
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchWorkflowAsksClientToStartTheWorkflowWhenProcessorIsNotSet()
    {
        $client = $this->replaceSingletonWithMock(Client::class);
        $engine = Engine::getInstance();

        $client
            ->expects($this->once())
            ->method('startWorkflow')
        ;

        $workflow = $this->createMock(WorkflowInterface::class);

        $outputs = $engine->dispatch([$workflow]);
        static::assertSame([null], $outputs);
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchTaskAsksClientToStartTaskWhenProcessorIsNotSet()
    {
        $client = $this->replaceSingletonWithMock(Client::class);
        $engine = Engine::getInstance();

        $task = $this->createMock(TaskInterface::class);

        $client
            ->expects($this->once())
            ->method('startTask')
            ->with($task)
        ;

        $outputs = $engine->dispatch([$task]);
        static::assertSame([null], $outputs);
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchUsesProcessorWhenItIsSet()
    {
        $engine = Engine::getInstance();
        $engine->setProcessor(new NullProcessor());

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow
            ->expects($this->never())
            ->method('handle')
        ;

        $task = $this->createMock(TaskInterface::class);
        $task
            ->expects($this->never())
            ->method('handle')
        ;

        $engine->dispatch([$workflow, $task]);
    }
}
