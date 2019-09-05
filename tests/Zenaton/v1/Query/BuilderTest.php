<?php

namespace Zenaton\Query;

use PHPUnit\Framework\TestCase;
use Zenaton\Client;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Test\Mock\Event\DummyEvent;
use Zenaton\Test\Mock\Workflow\NullWorkflow;
use Zenaton\Test\SingletonTesting;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Query\Builder
 */
class BuilderTest extends TestCase
{
    use SingletonTesting;

    /**
     * @covers ::__construct
     */
    public function testNewBuilderWithNotAWorkflow()
    {
        $this->expectException(ExternalZenatonException::class);

        $builder = new Builder(\DateTime::class);
    }

    /**
     * @covers ::find
     */
    public function testFindWhenSupplyingAnId()
    {
        $client = $this->replaceSingletonWithMock(Client::class);

        $client
            ->expects($this->once())
            ->method('findWorkflow')
            ->with(NullWorkflow::class, 'WorkflowFakeId')
        ;

        $builder = new Builder(NullWorkflow::class);
        $builder
            ->whereId('WorkflowFakeId')
            ->find()
        ;
    }

    /**
     * @covers ::send
     */
    public function testSend()
    {
        $client = $this->replaceSingletonWithMock(Client::class);

        $event = new DummyEvent();

        $client
            ->expects($this->once())
            ->method('sendEvent')
            ->with(NullWorkflow::class, 'WorkflowFakeId', $event)
        ;

        $builder = new Builder(NullWorkflow::class);
        $builder
            ->whereId('WorkflowFakeId')
            ->send($event)
        ;
    }

    /**
     * @covers ::kill
     */
    public function testKill()
    {
        $client = $this->replaceSingletonWithMock(Client::class);

        $client
            ->expects($this->once())
            ->method('killWorkflow')
            ->with(NullWorkflow::class, 'WorkflowFakeId')
        ;

        $builder = new Builder(NullWorkflow::class);
        $builder
            ->whereId('WorkflowFakeId')
            ->kill()
        ;
    }

    /**
     * @covers ::pause
     */
    public function testPause()
    {
        $client = $this->replaceSingletonWithMock(Client::class);

        $client
            ->expects($this->once())
            ->method('pauseWorkflow')
            ->with(NullWorkflow::class, 'WorkflowFakeId')
        ;

        $builder = new Builder(NullWorkflow::class);
        $builder
            ->whereId('WorkflowFakeId')
            ->pause()
        ;
    }

    /**
     * @covers ::resume
     */
    public function testResume()
    {
        $client = $this->replaceSingletonWithMock(Client::class);

        $client
            ->expects($this->once())
            ->method('resumeWorkflow')
            ->with(NullWorkflow::class, 'WorkflowFakeId')
        ;

        $builder = new Builder(NullWorkflow::class);
        $builder
            ->whereId('WorkflowFakeId')
            ->resume()
        ;
    }
}
