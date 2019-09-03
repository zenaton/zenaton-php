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
     *
     * @internal should not be called by user code.
     */
    public static function unexpectedStatusCode($code)
    {
        return new static("The API responded with an unexpected status code: {$code}.");
    }

    /**
     * Creates a new instance of the exception when a connection error was received.
     *
     * @return self
     *
     * @internal should not be called by user code.
     */
    public static function connectionError(\Exception $previous)
    {
        return new static("A connection error occurred while trying to send a request to the Zenaton API: {$previous->getMessage()}.", 0, $previous);
    }

    /**
     * Creates a new instance of the exception when an exception is thrown.
     *
     * @return self
     *
     * @internal should not be called by user code.
     */
    public static function fromException(\Exception $previous)
    {
        return new static("An exception was thrown while trying to send a request to the Zenaton API: {$previous->getMessage()}.", 0, $previous);
    }

    /**
     * Creates a new instance of the exception when the response body contains invalid JSON.
     *
     * @param string $body
     * @param string $error
     *
     * @return self
     *
     * @internal should not be called by user code.
     */
    public static function cannotParseResponseBody($body, $error)
    {
        $message = <<<'MESSAGE'
Cannot parse response body coming from the Zenaton API: %s.

The following response body was returned from the API:
%s
MESSAGE;

        return new static(\sprintf($message, $error, $body));
    }

    /**
     * Creates a new instance of the exception when an error list is received.
     *
     * @return self
     *
     * @internal should not be called by user code.
     */
    public static function fromErrorList(array $errors)
    {
        $errorMessages = implode("\n", array_map(static function ($error) {
            return '  - '.$error['message'];
        }, $errors));

        $message = <<<MESSAGE
The Zenaton API returned some errors:
{$errorMessages}
MESSAGE;

        return new static($message);
    }
}
