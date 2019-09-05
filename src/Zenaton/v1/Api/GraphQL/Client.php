<?php

namespace Zenaton\Api\GraphQL;

use Zenaton\Exceptions\ApiException;
use Zenaton\Exceptions\ConnectionErrorException;
use Zenaton\Services\Http;

/**
 * @internal should not be called by user code
 */
class Client
{
    /** @var string */
    private $endpoint;
    /** @var array */
    private $options;
    /** @var \Zenaton\Services\Http */
    private $httpClient;

    /**
     * Client constructor.
     *
     * The `options` parameter accepts the following keys:
     *  - `headers`: A map of headers to add to every request that will be made using this client instance.
     *
     * @param string $endpoint
     */
    public function __construct(Http $httpClient, $endpoint, array $options)
    {
        $this->endpoint = $endpoint;
        $this->options = $options;
        $this->httpClient = $httpClient;
    }

    /**
     * Sends a request to a GraphQL endpoint.
     *
     * @param string $query
     *
     * @throws \Zenaton\Exceptions\ApiException if there is a connection error or an error is returned from the API
     *
     * @return array
     */
    public function request($query, array $variables = [], array $headers = [])
    {
        try {
            $response = $this->httpClient->post($this->endpoint, \json_encode(['query' => $query, 'variables' => $variables]), ['headers' => $this->getRequestHeaders($headers)]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        } catch (\Exception $e) {
            throw ApiException::fromException($e);
        }

        if ($response->code === 403) {
            throw ApiException::unauthenticated($response->request->headers['App-Id'], $response->request->headers['Api-Token']);
        }

        $decoded = \json_decode($response->raw_body, true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::cannotParseResponseBody($response->raw_body, \json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Returns the headers to add to GraphQL requests.
     *
     * @return array
     */
    private function getRequestHeaders(array $headers)
    {
        $clientHeaders = isset($this->options['headers']) ? $this->options['headers'] : [];

        return \array_merge($clientHeaders, $headers);
    }
}
