<?php

namespace Zenaton\Exceptions;

/**
 * Exception thrown when users must be informed that their agent needs to be updated to use a specific feature.
 */
class AgentUpdateRequiredException extends AgentException
{
    /**
     * @param string          $expected Expected version. Can be written as a version number or as a version constraint (e.g. "0.5.0" or ">= 0.5" or "at least 0.5.0").
     * @param null|string     $actual   Actual version. If provided, will be used in the error message to give more information to the user.
     * @param null|\Exception $previous previous exception
     */
    public function __construct($expected, $actual = null, $previous = null)
    {
        $message = "This feature requires your agent version to be {$expected}.";
        if ($actual) {
            $message .= " Your agent version is {$actual}.";
        }
        $message .= ' Please update your agent and try again.';

        parent::__construct($message, 0, $previous);
    }
}
