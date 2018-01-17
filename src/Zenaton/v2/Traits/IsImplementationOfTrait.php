<?php

namespace Zenaton\v2\Traits;

trait IsImplementationOfTrait
{
    protected function isImplementationOf($name, $class)
    {
        if (is_string($name)) {
            $implements = @class_implements($name);
            if (is_array($implements)) {
                return isset($implements[$class]);
            }
        }

        return false;
    }
}
