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
     * @param array  $options
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
     * @param array  $variables
     * @param array  $headers
     *
     * @return array
     *
     * @throws \Zenaton\Exceptions\ApiException if there is a connection error or an error is returned from the API
     */
    public function request($query, array $variables = [], array $headers = [])
    {
        try {
            $response = $this->httpClient->post($this->endpoint, \json_encode(['query' => $query, 'variables' => $variables]), ['headers' => $this->getRequestHeaders($headers)]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        return \json_decode($response->raw_body, true);
    }

    /**
     * Returns the headers to add to GraphQL requests
     *
     * @param array $headers
     *
     * @return array
     */
    private function getRequestHeaders(array $headers)
    {
        $clientHeaders = isset($this->options['headers']) ? $this->options['headers'] : [];

        return \array_merge($clientHeaders, $headers);
    }
}
