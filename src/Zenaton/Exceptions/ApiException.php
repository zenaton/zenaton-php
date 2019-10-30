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
     * @internal should not be called by user code
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
     * @internal should not be called by user code
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
     * @internal should not be called by user code
     */
    public static function fromException(\Exception $previous)
    {
        return new static("An exception was thrown while trying to send a request to the Zenaton API: {$previous->getMessage()}.", 0, $previous);
    }

    public static function unauthenticated($appId, $apiToken)
    {
        $message = <<<'MESSAGE'
Authentication refused by the Zenaton API using the following credentials:
- App Id: %s
- Api Token: %s

This can mean you forgot to set your App Id and Api Token on the `Zenaton\Client` object.
You can set your credentials using the `Zenaton\Client::init()` method.
MESSAGE;

        return new static(\sprintf($message, $appId, $apiToken));
    }

    /**
     * Creates a new instance of the exception when the response body contains invalid JSON.
     *
     * @param string $body
     * @param string $error
     *
     * @return self
     *
     * @internal should not be called by user code
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
     * @internal should not be called by user code
     */
    public static function fromErrorList(array $errors)
    {
        $hasWrongErrorFormat = \array_reduce($errors, static function ($carry, $error) {
            return $carry || !\is_array($error) || !isset($error['message']) || !\is_string($error['message']);
        }, false);

        if ($hasWrongErrorFormat) {
            $errorMessages = \var_export($errors, true);
        } else {
            $errorMessages = \implode("\n", array_map(static function ($error) {
                return '  - '.$error['message'];
            }, $errors));
        }

        $message = <<<MESSAGE
The Zenaton API returned some errors:
{$errorMessages}
MESSAGE;

        return new static($message);
    }
}
