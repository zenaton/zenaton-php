<?php

namespace Zenaton\Query;

use Zenaton\Test\Mock\Workflow\NullWorkflow;
use Zenaton\Client;
use Zenaton\Test\Mock\Event\DummyEvent;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Test\SingletonTesting;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    use SingletonTesting;

    public function testNewBuilderWithNotAWorkflow()
    {
        $this->expectException(ExternalZenatonException::class);

        $builder = new Builder(\DateTime::class);
    }

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
