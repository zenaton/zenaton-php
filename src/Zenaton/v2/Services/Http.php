<?php

namespace Zenaton\v2\Services;

use Httpful\Request as Httpful;
use Httpful\Exception\ConnectionErrorException;
use Zenaton\Exception\ConnectionErrorException as ZenatonConnectionErrorException;
use Zenaton\Exceptions\InternalZenatonException;

class Http
{
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
        try {
            $response = $f();

            if ($response->hasErrors()) {
                throw new InternalZenatonException($response->raw_body, $response->code);
            }

            return $response->body;
        } catch (ConnectionErrorException $e) {
            throw new ZenatonConnectionErrorException($e->getCurlErrorString(), $e->getCurlErrorNumber());
        }
    }
}
