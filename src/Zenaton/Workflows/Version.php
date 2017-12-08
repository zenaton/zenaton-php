<?php

namespace Zenaton\Workflows;

use ReflectionClass;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\Zenatonable;

abstract class Version implements WorkflowInterface
{
    use Zenatonable;

    protected $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }

    // useful for local implementation
    public function handle()
    {
        return ($this->getCurrentImplementation())->handle();
    }

    // class name of current implementation
    abstract protected function current();

    // true current implementation
    public function getCurrentImplementation()
    {
        return (new ReflectionClass($this->current()))->newInstanceArgs($this->args);
    }
}
