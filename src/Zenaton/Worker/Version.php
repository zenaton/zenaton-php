<?php

namespace Zenaton\Worker;

use ReflectionClass;

abstract class Version
{
    protected $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }

    // useful for local implementation
    public function handle()
    {
        return ($this->getCurrentInstance())->handle();
    }

    // class name of current implementation
    abstract protected function current();

    // true instance of current implementation
    public function getCurrentInstance()
    {
        return (new ReflectionClass($this->current()))->newInstanceArgs($this->args);
    }
}
