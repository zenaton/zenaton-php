<?php

namespace Zenaton\Exceptions;

/**
 * Exception thrown when the agent is not listening to the application.
 */
class AgentNotListeningException extends AgentException
{
    /**
     * @param string          $appId    Application Id
     * @param string          $appEnv   Application environment
     * @param null|\Exception $previous Previous exception
     */
    public function __construct($appId, $appEnv, $previous = null)
    {
        $message = "Your agent is not listening to application \"{$appId}\" on environment \"{$appEnv}\"";

        parent::__construct($message, 0, $previous);
    }
}
