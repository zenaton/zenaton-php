<?php

namespace Zenaton\Engine;

use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Test\ClientInvolvedTestCase;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Client;
use Zenaton\Test\Mock\Processor\NullProcessor;

class EngineTest extends ClientInvolvedTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::destroyEngineSingleton();
    }

    public function tearDown()
    {
        static::destroyEngineSingleton();

        parent::tearDown();
    }

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

    public function testExecuteJobNotWorkflowOrTaskThrowsAnException()
    {
        $this->expectException(InvalidArgumentException::class);

        $job = new \DateTime();

        $engine = Engine::getInstance();
        $engine->execute([$job]);
    }

    public function testDispatchWorkflowAsksClientToStartTheWorkflowWhenProcessorIsNotSet()
    {
        $engine = Engine::getInstance();
        $client = $this->createClientMock();

        $client
            ->expects($this->once())
            ->method('startWorkflow')
        ;

        $workflow = $this->createMock(WorkflowInterface::class);

        $outputs = $engine->dispatch([$workflow]);
        static::assertSame([null], $outputs);
    }

    public function testDispatchTaskExecuteHandleMethodOfTaskWhenProcessorIsNotSet()
    {
        $engine = Engine::getInstance();

        $task = $this->createMock(TaskInterface::class);
        $task
            ->expects($this->once())
            ->method('handle')
        ;

        $outputs = $engine->dispatch([$task]);
        static::assertSame([null], $outputs);
    }

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

    /**
     * Destroys the Engine singleton.
     */
    private static function destroyEngineSingleton()
    {
        $terminator = (static function () {
            static::$instance = null;
        })->bindTo(null, Engine::class);

        $terminator();
    }

    /**
     * Injects a mocked Client instance into the Engine singleton instance.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function createClientMock()
    {
        $engine = Engine::getInstance();
        $mock = $this->createMock(Client::class);

        $injector = (function ($mock) {
            $this->client = $mock;
        })->call($engine, $mock);

        return $mock;
    }
}
