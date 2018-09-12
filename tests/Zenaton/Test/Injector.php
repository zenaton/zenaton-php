<?php

namespace Zenaton\Test;

trait Injector
{
    /**
     * Binds a given closure to an object and executes it.
     *
     * The given closure will not receive any parameter.
     *
     * @param object $object
     */
    protected function inject(\Closure $closure, $object)
    {
        $boundedClosure = $closure->bindTo($object, get_class($object));
        $boundedClosure();
    }
}
