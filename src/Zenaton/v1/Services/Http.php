<?php

namespace Zenaton\Services;

use Httpful\Exception\ConnectionErrorException;
use Httpful\Request as Httpful;
use Zenaton\Exceptions\ConnectionErrorException as ZenatonConnectionErrorException;

class Http
{
    public function get($url)
    {
        $f = function () use ($url) {
            return Httpful::get($url)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }

    public function post($url, $body)
    {
        $f = function () use ($url, $body) {
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
        $f = function () use ($url, $body) {
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
            return $f();
        } catch (ConnectionErrorException $e) {
            throw new ZenatonConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
