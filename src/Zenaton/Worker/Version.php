<?php

namespace Zenaton\Worker;

use ReflectionClass;

abstract class Version
{
    private $_workflow_args;

    public function __construct()
    {
        $this->_workflow_args = func_get_args();
    }

    public function handle()
    {
        return ($this->getClass())->handle();
    }

    public function class()
    {
        return (new ReflectionClass($this->current()))->newInstanceArgs($this->_workflow_args);
    }

    abstract protected function current();
}
