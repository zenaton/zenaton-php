<?php

namespace Zenaton\Exceptions;

/**
 * Exception thrown when a response coming from the API is not what was expected.
 */
class ApiException extends ZenatonException
{
    /**
     * Creates a new instance of the exception when an unexpected status code was received.
     *
     * @param int $code
     *
     * @return \Zenaton\Exceptions\ApiException
     */
    public static function unexpectedStatusCode($code)
    {
        return new static("The API responded with an unexpected status code: {$code}.");
    }
}
