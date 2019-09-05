<?php

namespace Zenaton\Services;

use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use Zenaton\Exceptions\ConnectionErrorException as ZenatonConnectionErrorException;

/**
 * @internal should not be called by user code
 */
class Http
{
    /**
     * Make a GET request.
     *
     * @param string $url
     *
     * @throws \Zenaton\Exceptions\ConnectionErrorException if there is a connection error while sending the HTTP request
     *
     * @return \Httpful\Response
     */
    public function get($url)
    {
        $f = static function () use ($url) {
            return Request::get($url)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }

    /**
     * Make a POST request.
     *
     * The `options` parameter can contain the following:
     *      headers: An array containing string keys and string values that will be added as request headers.
     *
     * @param string $url
     * @param string $body
     * @param array  $options
     *
     * @throws \Zenaton\Exceptions\ConnectionErrorException if there is a connection error while sending the HTTP request
     *
     * @return \Httpful\Response
     */
    public function post($url, $body, $options = [])
    {
        $f = static function () use ($url, $body, $options) {
            /** @var Request $request */
            $request = Request::post($url)
                ->sendsJson()
                ->body($body)
                ->expectsJson()
            ;

            if (isset($options['headers'])) {
                foreach ($options['headers'] as $name => $value) {
                    $request->addHeader($name, $value);
                }
            }

            return $request->send();
        };

        return $this->request($f);
    }

    /**
     * Make a PUT request.
     *
     * @param string $url
     * @param string $body
     *
     * @throws \Zenaton\Exceptions\ConnectionErrorException if there is a connection error while sending the HTTP request
     *
     * @return \Httpful\Response
     */
    public function put($url, $body)
    {
        $f = static function () use ($url, $body) {
            return Request::put($url)
                ->sendsJson()
                ->body($body)
                ->expectsJson()
                ->send();
        };

        return $this->request($f);
    }

    /**
     * Execute the given closure and catch `ConnectionErrorException` to throw our own `ConnectionErrorException` instead.
     *
     * @param \Closure $f
     *
     * @throws \Zenaton\Exceptions\ConnectionErrorException
     *
     * @return \Httpful\Response
     */
    protected function request($f)
    {
        try {
            return $f();
        } catch (ConnectionErrorException $e) {
            throw new ZenatonConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
