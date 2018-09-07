<?php

namespace Zenaton;

use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Http;
use Zenaton\Workflows\Version;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Test\ClientInvolvedTestCase;
use Zenaton\Test\Mock\Event\DummyEvent;
use Zenaton\Test\Mock\Workflow\NullWorkflow;

final class ClientTest extends ClientInvolvedTestCase
{
    public function testInit()
    {
        $client = Client::getInstance();

        $assertions = function () {
            assertSame('FakeAppId', $this->appId);
            assertSame('FakeApiToken', $this->apiToken);
            assertSame('FakeAppEnv', $this->appEnv);
        };

        $assertions->call($client);
    }

    public function testStartBasicWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('post')
        ;

        $workflow = $this->createMock(WorkflowInterface::class);

        $client->startWorkflow($workflow);
    }

    public function testStartVersionWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('post')
        ;

        $workflow = $this
            ->getMockBuilder(Version::class)
            ->setMethods([
                'versions',
            ])
            ->getMock()
        ;
        $workflow
            ->expects($this->any())
            ->method('versions')
            ->willReturn([
                NullWorkflow::class,
            ])
        ;

        $client->startWorkflow($workflow);
    }

    public function testStartWorkflowWithId()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('post')
        ;

        $workflow = $this
            ->getMockBuilder(WorkflowInterface::class)
            ->setMethods([
                'getId',
                'handle',
            ])
            ->getMock()
        ;
        $workflow
            ->expects($this->any())
            ->method('getId')
            ->willReturn('WorkflowIdentifier')
        ;

        $client->startWorkflow($workflow);
    }

    /**
     * @dataProvider getTestStartWorkflowWithInvalidIdData
     */
    public function testStartWorkflowWithInvalidId($identifier)
    {
        $this->expectException(InvalidArgumentException::class);

        Client::init('FakeAppId', 'FakeApiToken', 'FakeAppEnv');
        $client = Client::getInstance();
        $workflow = $this->createWorkflowWithIdentifierMock($identifier);

        $client->startWorkflow($workflow);
    }

    public function getTestStartWorkflowWithInvalidIdData()
    {
        yield [null];
        yield [[]];
        yield ['a very l'.str_repeat('o', 256).'ng identifier'];
    }

    public function testKillWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('put')
            ->with(
                'http://localhost:4001/api/v_newton/instances?app_env=FakeAppEnv&app_id=FakeAppId&custom_id=Soon to be dead workflow&',
                [
                    'programming_language' => 'PHP',
                    'name' => NullWorkflow::class,
                    'mode' => 'kill',
                ]
            )
        ;

        $client->killWorkflow(NullWorkflow::class, 'Soon to be dead workflow');
    }

    public function testPauseWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('put')
            ->with(
                'http://localhost:4001/api/v_newton/instances?app_env=FakeAppEnv&app_id=FakeAppId&custom_id=Soon to be paused workflow&',
                [
                    'programming_language' => 'PHP',
                    'name' => NullWorkflow::class,
                    'mode' => 'pause',
                ]
            )
        ;

        $client->pauseWorkflow(NullWorkflow::class, 'Soon to be paused workflow');
    }

    public function testResumeWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('put')
            ->with(
                'http://localhost:4001/api/v_newton/instances?app_env=FakeAppEnv&app_id=FakeAppId&custom_id=Soon to be resumed workflow&',
                [
                    'programming_language' => 'PHP',
                    'name' => NullWorkflow::class,
                    'mode' => 'run',
                ]
            )
        ;

        $client->resumeWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');
    }

    public function testFindWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $data = (object) [
            'data' => (object) [
                'name' => NullWorkflow::class,
                'properties' => '{"a":[1,2,3],"s":[]}',
            ],
        ];

        $http
            ->expects($this->once())
            ->method('get')
            ->with('https://zenaton.com/api/v1/instances?api_token=FakeApiToken&app_env=FakeAppEnv&app_id=FakeAppId&custom_id=Soon to be resumed workflow&name=Zenaton\Test\Mock\Workflow\NullWorkflow&programming_language=PHP&')
            ->willReturn($data)
        ;

        $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');
    }

    public function testSendEvent()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('post')
            ->with(
                'http://localhost:4001/api/v_newton/events?app_env=FakeAppEnv&app_id=FakeAppId&',
                [
                    'programming_language' => 'PHP',
                    'name' => NullWorkflow::class,
                    'custom_id' => 'Workflow to send event to',
                    'event_name' => DummyEvent::class,
                    'event_input' => '{"a":[],"s":[]}',
                ]
            )
        ;

        $event = new DummyEvent();

        $client->sendEvent(NullWorkflow::class, 'Workflow to send event to', $event);
    }

    /**
     * Creates a mock of a Workflow having an identifier.
     *
     * @param string $identifier The workflow identifier
     */
    private function createWorkflowWithIdentifierMock($identifier = 'WorkflowIdentifier')
    {
        $workflow = $this
            ->getMockBuilder(WorkflowInterface::class)
            ->setMethods([
                'getId',
                'handle',
            ])
            ->getMock()
        ;
        $workflow
            ->expects($this->any())
            ->method('getId')
            ->willReturn($identifier)
        ;

        return $workflow;
    }

    /**
     * Inject a mocked Http instance into the Client singleton instance.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Http
     */
    private function createHttpMock()
    {
        $client = Client::getInstance();
        $mock = $this->createMock(Http::class);

        $injector = (function ($mock) {
            $this->http = $mock;
        })->call($client, $mock);

        return $mock;
    }
}
