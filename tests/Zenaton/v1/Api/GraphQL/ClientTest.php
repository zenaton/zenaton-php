<?php

namespace Zenaton\Api\GraphQL;

use Httpful\Request;
use Httpful\Response;
use PHPUnit\Framework\TestCase;
use Zenaton\Exceptions\ApiException;
use Zenaton\Services\Http;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Api\GraphQL\Client
 */
class ClientTest extends TestCase
{
    const ENDPOINT = 'http://localhost/graphql';

    /**
     * @covers ::request
     */
    public function testRequestThrowsAnExceptionInCaseOfConnectionError()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('A connection error occurred while trying to send a request to the Zenaton API: Previous exception message');

        $http = $this->createMock(Http::class);
        $http
            ->method('post')
            ->willThrowException(ApiException::connectionError(new \RuntimeException('Previous exception message')))
        ;

        $client = new Client($http, static::ENDPOINT, []);
        $client->request('');
    }

    /**
     * @covers ::request
     */
    public function testRequestThrowsAnExceptionInCaseOfInvalidJsonReturned()
    {
        $this->expectException(ApiException::class);

        $mockedResponse = new Response(
            'Crappy content returned by the server <strong>possibly containing some weird html</strong>, etc.',
            "HTTP/1.1 400 OK\n",
            Request::init()
        );

        $http = $this->createMock(Http::class);
        $http
            ->method('post')
            ->willReturn($mockedResponse)
        ;

        $client = new Client($http, static::ENDPOINT, []);
        $response = $client->request('');
    }

    /**
     * @covers ::request
     */
    public function testRequestThrowsAnExceptionInCaseOfHttpfulUnableToParseJson()
    {
        $this->expectException(ApiException::class);

        $http = $this->createMock(Http::class);
        $http
            ->method('post')
            ->willThrowException(new \Exception('Unable to parse response as JSON'))
        ;

        $client = new Client($http, static::ENDPOINT, []);
        $response = $client->request('');
    }

    /**
     * @covers ::request
     */
    public function testRequestReturnsDecodedResponseBody()
    {
        $mockedResponse = new Response(
            '{"data":{"createTaskSchedule":{"schedule":{"cron":"* * * * *","id":"9c3cbc93-f394-4a3d-ab3e-5f6f884d9ab9","insertedAt":"2019-08-20T15:22:31.859478Z","name":"Zenaton\\\\Test\\\\Mock\\\\Tasks\\\\NullTask","target":{"codePathVersion":null,"initialLibraryVersion":null,"name":"Zenaton\\\\Test\\\\Mock\\\\Tasks\\\\NullTask","programmingLanguage":"PHP","properties":"{\"a\":[],\"s\":[]}","type":"TASK"},"updatedAt":"2019-08-20T15:22:31.859478Z"}}}}',
            "HTTP/1.1 200 OK\n",
            Request::init()
        );

        $http = $this->createMock(Http::class);
        $http
            ->method('post')
            ->willReturn($mockedResponse)
        ;

        $client = new Client($http, static::ENDPOINT, []);
        $response = $client->request('');

        static::assertInternalType('array', $response);
        static::assertEquals([
            'data' => [
                'createTaskSchedule' => [
                    'schedule' => [
                        'cron' => '* * * * *',
                        'id' => '9c3cbc93-f394-4a3d-ab3e-5f6f884d9ab9',
                        'insertedAt' => '2019-08-20T15:22:31.859478Z',
                        'name' => 'Zenaton\Test\Mock\Tasks\NullTask',
                        'target' => [
                            'codePathVersion' => null,
                            'initialLibraryVersion' => null,
                            'name' => 'Zenaton\Test\Mock\Tasks\NullTask',
                            'programmingLanguage' => 'PHP',
                            'properties' => '{"a":[],"s":[]}',
                            'type' => 'TASK',
                        ],
                        'updatedAt' => '2019-08-20T15:22:31.859478Z',
                    ],
                ],
            ],
        ], $response);
    }
}
