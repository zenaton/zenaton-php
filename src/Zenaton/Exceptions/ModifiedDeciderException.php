<?php

namespace Zenaton\Exceptions;

class ModifiedDeciderException extends ExternalZenatonException
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message ? : "Error: your workflow has changed - please use versioning", $code, $previous);
    }
}
