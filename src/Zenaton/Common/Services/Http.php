<?php

namespace Zenaton\Common\Services;

use Httpful\Request as Httpful;
use Zenaton\Common\Exceptions\InternalZenatonException;
use Zenaton\Common\Services\Metrics;

class Http
{
    public function __construct()
    {
        $this->metrics = Metrics::getInstance();
    }

    public function post($url, $body)
    {
        $start = microtime(true);

        $response = Httpful::post($url)
            ->sendsJson()
            ->body($body)
            ->expectsJson()
            ->send();

        $this->metrics->addNetworkDuration(microtime(true) - $start);

        return $response->body;
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
