<?php

namespace Zenaton\Common\Services;

use Httpful\Request as Httpful;
use Zenaton\Common\Services\Metrics;
use Httpful\Exception\ConnectionErrorException;
use Zenaton\Common\Exceptions\InternalZenatonException;

class Http
{
    const ENV_WORKER_PORT = 'ZENATON_WORKER_PORT';

    public function __construct()
    {
        $this->metrics = Metrics::getInstance();
    }

    public function post($url, $body)
    {
        $start = microtime(true);

        try {
            $response = Httpful::post($url)
                ->sendsJson()
                ->body($body)
                ->expectsJson()
                ->send();

            if ($response->hasErrors()) {
                throw new InternalZenatonException('Zenaton worker: ' . $response->raw_body, $response->code);
            }

            return $response->body;

        } catch (ConnectionErrorException $e) {
            $port = getenv(self::ENV_WORKER_PORT) ? : 4001;
            $error = "Zenaton worker: connection error. Please Check that you've started a zenaton worker on PORT ".$port;
            throw new InternalZenatonException($error, 0);
        }

        $this->metrics->addNetworkDuration(microtime(true) - $start);
    }

    public function get($url)
    {
        $start = microtime(true);

        $response = Httpful::get($url)
            ->expectsJson()
            ->send();

        $this->metrics->addNetworkDuration(microtime(true) - $start);

        return $response->body;
    }

    public function put($url, $body)
    {
        $start = microtime(true);

        $response = Httpful::put($url)
            ->sendsJson()
            ->body($body)
            ->expectsJson()
            ->send();

        $this->metrics->addNetworkDuration(microtime(true) - $start);

        return $response->body;
    }
}
