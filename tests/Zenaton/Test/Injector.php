<?php

namespace Zenaton\Test;

trait Injector
{
    /**
     * Binds a given closure to an object and executes it.
     *
     * @param \Closure $closure
     * @param object $object
     */
    protected function inject(\Closure $closure, $object)
    {
        $closure->call($object);
    }
}
