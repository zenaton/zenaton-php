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

    public function get($url)
    {
        $f = function() use ($url) {
            return Httpful::get($url)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }
    
    public function post($url, $body)
    {
        $f = function() use ($url, $body) {
            return Httpful::post($url)
                ->sendsJson()
                ->body($body)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }

    public function put($url, $body)
    {
        $f = function() use ($url, $body) {
            return Httpful::put($url)
                ->sendsJson()
                ->body($body)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }

    protected function request($f)
    {
        $start = microtime(true);

        try {
            $response = $f();

            if ($response->hasErrors()) {
                throw new InternalZenatonException('Zenaton worker: ' . $response->raw_body, $response->code);
            }

            $this->metrics->addNetworkDuration(microtime(true) - $start);

            return $response->body;

        } catch (ConnectionErrorException $e) {
            $port = getenv(self::ENV_WORKER_PORT) ? : 4001;
            $error = "Zenaton worker: connection error. Please Check that you've started a zenaton worker on PORT ".$port;
            throw new InternalZenatonException($error, 0);
        }
    }
}
