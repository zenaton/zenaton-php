<?php

namespace Zenaton\Exceptions;

class ZenatonException extends \Exception
{
    // custom string representation of object
    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}
