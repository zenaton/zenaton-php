<?php

namespace Zenaton;

use Httpful\Request;
use Httpful\Response;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Zenaton\Exceptions\ApiException;
use Zenaton\Exceptions\ConnectionErrorException;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Http;
use Zenaton\Test\Injector;
use Zenaton\Test\Mock\Event\DummyEvent;
use Zenaton\Test\Mock\Tasks\NullTask;
use Zenaton\Test\Mock\Workflow\NullWorkflow;
use Zenaton\Test\SingletonTesting;
use Zenaton\Workflows\Version as VersionedWorkflow; // Alias is required because of a bug in PHP 5.6 namespace shadowing. See https://bugs.php.net/bug.php?id=66862.

final class ClientTest extends TestCase
{
    use SingletonTesting;
    use Injector;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Make sure Client singleton instance is destroyed before running any of those tests
        static::destroySingleton(Client::class);
    }

    public function setUp()
    {
        parent::setUp();

        Client::init('FakeAppId', 'FakeApiToken', 'FakeAppEnv');
    }

    public function tearDown()
    {
        // Make sure Client singleton instance is destroyed between tests
        static::destroySingleton(Client::class);

        parent::tearDown();
    }

    public function testInit()
    {
        $client = Client::getInstance();

        $assertions = function () {
            assertSame('FakeAppId', $this->appId);
            assertSame('FakeApiToken', $this->apiToken);
            assertSame('FakeAppEnv', $this->appEnv);
        };

        $bounded = $assertions->bindTo($client, get_class($client));
        $bounded();
    }

    public function testStartBasicWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects(static::once())
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
            ->expects(static::once())
            ->method('post')
        ;

        $workflow = $this
            ->getMockBuilder(VersionedWorkflow::class)
            ->setMethods([
                'versions',
            ])
            ->getMock()
        ;
        $workflow
            ->expects(static::any())
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
            ->expects(static::once())
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
            ->expects(static::any())
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
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(function () {
                return Uuid::fromString('194910b6-cadd-4d2a-8d55-366a09654df6');
            })
        ;
        $http = $this->createHttpMock();
        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
        ;

        $client->killWorkflow(NullWorkflow::class, 'Soon to be dead workflow');
    }

    public function testPauseWorkflow()
    {
        $client = Client::getInstance();
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(function () {
                return Uuid::fromString('c9d5de11-a513-408b-bc30-82a85fe82c07');
            })
        ;
        $http = $this->createHttpMock();
        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
        ;

        $client->pauseWorkflow(NullWorkflow::class, 'Soon to be paused workflow');
    }

    public function testResumeWorkflow()
    {
        $client = Client::getInstance();
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(function () {
                return Uuid::fromString('1ed3a42b-69c4-4a4c-b1ec-fb1dc1f483cb');
            })
        ;
        $http = $this->createHttpMock();
        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
        ;

        $client->resumeWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');
    }

    public function testFindWorkflow()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $body = (object) [
            'data' => (object) [
                'workflow' => (object) [
                    'name' => NullWorkflow::class,
                    'properties' => '{"a":[1,2,3],"s":[]}',
                ],
            ],
        ];
        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response->body = $body;

        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
            ->willReturn($response)
        ;

        $workflow = $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');

        static::assertInstanceOf(NullWorkflow::class, $workflow);
    }

    public function testFindWorkflowThrowsAnExceptionWhenApiReturnsAnError()
    {
        $this->expectException(ApiException::class);

        $client = Client::getInstance();

        $http = $this->createHttpMock();
        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
            ->willThrowException(new ConnectionErrorException('Connection error from testFindWorkflowThrowsAnExceptionWhenApiReturnsAnError', 42))
        ;

        $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');
    }

    public function testFindWorkflowReturnsNullWhenWorkflowDoesNotExists()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $body = (object) [
            'errors' => [
                (object) [
                    'type' => 'NOT_FOUND',
                    'message' => 'No workflow instance found with the id : 12',
                ],
            ],
        ];

        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response->body = $body;
        $response->code = 200;

        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
            ->willReturn($response)
        ;

        $workflow = $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');

        static::assertNull($workflow);
    }

    public function testSendEvent()
    {
        $client = Client::getInstance();
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(static function () {
                return Uuid::fromString('1ed3a42b-69c4-4a4c-b1ec-fb1dc1f483cb');
            })
        ;
        $http = $this->createHttpMock();
        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://api.zenaton.com/v1', static::anything(), static::anything())
        ;

        $event = new DummyEvent();

        $client->sendEvent(NullWorkflow::class, 'Workflow to send event to', $event);
    }

    public function testScheduleWorkflow()
    {
        $client = Client::getInstance();
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(function () {
                return Uuid::fromString('47f5f00a-f29a-4b65-a7a5-b365b26dd92e');
            })
        ;
        $http = $this->createHttpMock();
        $expectedQuery = <<<'BODY'
        mutation createWorkflowSchedule($input: CreateWorkflowScheduleInput!) {
            createWorkflowSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
BODY;
        $expectedVariables = [
            'input' => [
                'environmentName' => 'FakeAppEnv',
                'cron' => '* * * * *',
                'canonicalName' => NullWorkflow::class,
                'intentId' => '47f5f00a-f29a-4b65-a7a5-b365b26dd92e',
                'programmingLanguage' => 'PHP',
                'properties' => '{"a":[],"s":[]}',
                'workflowName' => NullWorkflow::class,
            ],
        ];
        $expectedBody = \json_encode(['query' => $expectedQuery, 'variables' => $expectedVariables]);
        $expectedOptions = [
            'headers' => [
                'App-Id' => 'FakeAppId',
                'Api-Token' => 'FakeApiToken',
            ],
        ];

        $mockedResponse = new Response(
            '{"data":{"createWorkflowSchedule":{"schedule":{"cron":"* * * * *","id":"47f5f00a-f29a-4b65-a7a5-b365b26dd92e","insertedAt":"2019-08-20T15:22:31.859478Z","name":"Zenaton\\\\Test\\\\Mock\\\\Workflow\\\\NullWorkflow","target":{"canonicalName":"Zenaton\\\\Test\\\\Mock\\\\Workflow\\\\NullWorkflow","codePathVersion":null,"initialLibraryVersion":null,"name":"Zenaton\\\\Test\\\\Mock\\\\Workflow\\\\NullWorkflow","programmingLanguage":"PHP","properties":"{\"a\":[],\"s\":[]}","type":"WORKFLOW"},"updatedAt":"2019-08-20T15:22:31.859478Z"}}}}',
            "HTTP/1.1 200 OK\n",
            Request::init()
        );

        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://gateway.zenaton.com/api', $expectedBody, $expectedOptions)
            ->willReturn($mockedResponse)
        ;
        $output = $client->scheduleWorkflow(new NullWorkflow(), '* * * * *');

        static::assertNull($output);
    }

    public function testScheduleTask()
    {
        $client = Client::getInstance();
        $uuidFactory = $this->createUuidFactoryMock();
        $uuidFactory
            ->method('uuid4')
            ->willReturnCallback(function () {
                return Uuid::fromString('9c3cbc93-f394-4a3d-ab3e-5f6f884d9ab9');
            })
        ;
        $http = $this->createHttpMock();
        $expectedQuery = <<<'BODY'
        mutation createTaskSchedule($input: CreateTaskScheduleInput!) {
            createTaskSchedule(input: $input) {
                schedule {
                    id
                }
            }
        }
BODY;
        $expectedVariables = [
            'input' => [
                'environmentName' => 'FakeAppEnv',
                'cron' => '* * * * *',
                'intentId' => '9c3cbc93-f394-4a3d-ab3e-5f6f884d9ab9',
                'programmingLanguage' => 'PHP',
                'properties' => '{"a":[],"s":[]}',
                'taskName' => NullTask::class,
            ],
        ];
        $expectedBody = \json_encode(['query' => $expectedQuery, 'variables' => $expectedVariables]);
        $expectedOptions = [
            'headers' => [
                'App-Id' => 'FakeAppId',
                'Api-Token' => 'FakeApiToken',
            ],
        ];

        $mockedResponse = new Response(
            '{"data":{"createTaskSchedule":{"schedule":{"cron":"* * * * *","id":"9c3cbc93-f394-4a3d-ab3e-5f6f884d9ab9","insertedAt":"2019-08-20T15:22:31.859478Z","name":"Zenaton\\\\Test\\\\Mock\\\\Tasks\\\\NullTask","target":{"codePathVersion":null,"initialLibraryVersion":null,"name":"Zenaton\\\\Test\\\\Mock\\\\Tasks\\\\NullTask","programmingLanguage":"PHP","properties":"{\"a\":[],\"s\":[]}","type":"TASK"},"updatedAt":"2019-08-20T15:22:31.859478Z"}}}}',
            "HTTP/1.1 200 OK\n",
            Request::init()
        );

        $http
            ->expects(static::once())
            ->method('post')
            ->with('https://gateway.zenaton.com/api', $expectedBody, $expectedOptions)
            ->willReturn($mockedResponse)
        ;
        $output = $client->scheduleTask(new NullTask(), '* * * * *');

        static::assertNull($output);
    }

    /**
     * @dataProvider getTestGetWorkerUrlWithParamsAsArrayData
     */
    public function testGetWorkerUrlWithParamsAsArray($resource, $params, $expected)
    {
        $client = Client::getInstance();

        $actual = $client->getWorkerUrl($resource, $params);

        static::assertSame($expected, $actual);
    }

    public function getTestGetWorkerUrlWithParamsAsArrayData()
    {
        yield ['resource', [], 'http://localhost:4001/api/v_newton/resource?app_id=FakeAppId&app_env=FakeAppEnv'];
        yield ['resource/sub-resource', [], 'http://localhost:4001/api/v_newton/resource/sub-resource?app_id=FakeAppId&app_env=FakeAppEnv'];
        yield ['resource/sub-resource', ['custom_id' => 'zenaton'], 'http://localhost:4001/api/v_newton/resource/sub-resource?custom_id=zenaton&app_id=FakeAppId&app_env=FakeAppEnv'];
        yield ['resource/sub-resource', ['custom_id' => 'SpecialCharsé&@#"(!-_$ùàç+'], 'http://localhost:4001/api/v_newton/resource/sub-resource?custom_id=SpecialChars%C3%A9%26%40%23%22%28%21-_%24%C3%B9%C3%A0%C3%A7%2B&app_id=FakeAppId&app_env=FakeAppEnv'];
    }

    /**
     * @dataProvider getTestGetWorkerUrlWithParamsAsStringData
     */
    public function testGetWorkerUrlWithParamsAsString($resource, $params, $expected)
    {
        $client = Client::getInstance();

        // The library will use `trigger_error` function. We register an error handler to avoid phpunit thinking there is
        // an error in the test and marking test results as a failure.
        set_error_handler(function ($errno, $errstr) {
            static::assertEquals('You are running a Zenaton agent version <= 0.4.5 which is using deprecated code. Please consider upgrading your agent.', $errstr);
        }, E_USER_DEPRECATED);

        $actual = $client->getWorkerUrl($resource, $params);

        restore_error_handler();

        static::assertSame($expected, $actual);
    }

    public function getTestGetWorkerUrlWithParamsAsStringData()
    {
        yield ['resource', '', 'http://localhost:4001/api/v_newton/resource?app_env=FakeAppEnv&app_id=FakeAppId&'];
        yield ['resource/sub-resource', '', 'http://localhost:4001/api/v_newton/resource/sub-resource?app_env=FakeAppEnv&app_id=FakeAppId&'];
        yield ['resource/sub-resource', 'custom_id=zenaton', 'http://localhost:4001/api/v_newton/resource/sub-resource?app_env=FakeAppEnv&app_id=FakeAppId&custom_id=zenaton&'];
        yield ['resource/sub-resource', 'custom_id=SpecialCharsé&@#"(!-_$ùàç', 'http://localhost:4001/api/v_newton/resource/sub-resource?app_env=FakeAppEnv&app_id=FakeAppId&custom_id=SpecialCharsé&@#"(!-_$ùàç&'];
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
            ->expects(static::any())
            ->method('getId')
            ->willReturn($identifier)
        ;

        return $workflow;
    }

    /**
     * Inject a mocked Http instance into the Client singleton instance.
     *
     * @return Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createHttpMock()
    {
        $client = Client::getInstance();
        $mock = $this->createMock(Http::class);

        $this->inject(function () use ($mock) {
            $this->http = $mock;
        }, $client);

        return $mock;
    }

    /**
     * Inject a mocked UuidFactory instance into the Client singleton instance.
     */
    private function createUuidFactoryMock()
    {
        $client = Client::getInstance();
        $mock = $this->createMock(UuidFactoryInterface::class);

        $this->inject(function () use ($mock) {
            $this->uuidFactory = $mock;
        }, $client);

        return $mock;
    }
}
