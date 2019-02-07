<?php

namespace Zenaton;

use Httpful\Response;
use PHPUnit\Framework\TestCase;
use Zenaton\Exceptions\ApiException;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Http;
use Zenaton\Test\Injector;
use Zenaton\Test\Mock\Event\DummyEvent;
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
            ->getMockBuilder(VersionedWorkflow::class)
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
                'http://localhost:4001/api/v_newton/instances?custom_id=Soon+to+be+dead+workflow&app_id=FakeAppId&app_env=FakeAppEnv',
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
                'http://localhost:4001/api/v_newton/instances?custom_id=Soon+to+be+paused+workflow&app_id=FakeAppId&app_env=FakeAppEnv',
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
                'http://localhost:4001/api/v_newton/instances?custom_id=Soon+to+be+resumed+workflow&app_id=FakeAppId&app_env=FakeAppEnv',
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

        $body = (object) [
            'data' => (object) [
                'name' => NullWorkflow::class,
                'properties' => '{"a":[1,2,3],"s":[]}',
            ],
        ];
        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response
            ->expects($this->once())
            ->method('hasErrors')
            ->willReturn(false)
        ;
        $response->body = $body;

        $http
            ->expects($this->once())
            ->method('get')
            ->with('https://api.zenaton.com/v1/instances?custom_id=Soon+to+be+resumed+workflow&name=Zenaton%5CTest%5CMock%5CWorkflow%5CNullWorkflow&programming_language=PHP&api_token=FakeApiToken&app_id=FakeAppId&app_env=FakeAppEnv')
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

        $body = (object) [
            'error' => 'No workflow instance found with the id : 12',
        ];

        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response
            ->expects($this->once())
            ->method('hasErrors')
            ->willReturn(true)
        ;
        $response->body = $body;
        $response->code = 500;

        $http
            ->expects($this->once())
            ->method('get')
            ->with('https://api.zenaton.com/v1/instances?custom_id=Soon+to+be+resumed+workflow&name=Zenaton%5CTest%5CMock%5CWorkflow%5CNullWorkflow&programming_language=PHP&api_token=FakeApiToken&app_id=FakeAppId&app_env=FakeAppEnv')
            ->willReturn($response)
        ;

        $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');
    }

    public function testFindWorkflowReturnsNullWhenWorkflowDoesNotExists()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $body = (object) [
            'error' => 'No workflow instance found with the id : 12',
        ];

        $response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $response->body = $body;
        $response->code = 404;

        $http
            ->expects($this->once())
            ->method('get')
            ->with('https://api.zenaton.com/v1/instances?custom_id=Soon+to+be+resumed+workflow&name=Zenaton%5CTest%5CMock%5CWorkflow%5CNullWorkflow&programming_language=PHP&api_token=FakeApiToken&app_id=FakeAppId&app_env=FakeAppEnv')
            ->willReturn($response)
        ;

        $workflow = $client->findWorkflow(NullWorkflow::class, 'Soon to be resumed workflow');

        static::assertNull($workflow);
    }

    public function testSendEvent()
    {
        $client = Client::getInstance();
        $http = $this->createHttpMock();

        $http
            ->expects($this->once())
            ->method('post')
            ->with(
                'http://localhost:4001/api/v_newton/events?app_id=FakeAppId&app_env=FakeAppEnv',
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
            ->expects($this->any())
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
}
