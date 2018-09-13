<?php

namespace Zenaton\Workflows;

use ReflectionClass;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\IsImplementationOfTrait;
use Zenaton\Traits\Zenatonable;

abstract class Version implements WorkflowInterface
{
    use Zenatonable;
    use IsImplementationOfTrait;

    protected $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }

    // history of implementation
    abstract public function versions();

    // useful for local implementation
    public function handle()
    {
        $current = $this->getCurrentImplementation();

        return $current->handle();
    }

    // current implementation
    public function getCurrentImplementation()
    {
        return (new ReflectionClass($this->current()))->newInstanceArgs($this->args);
    }

    // class name of current implementation
    public function current()
    {
        return array_values(array_slice($this->_getVersions(), -1))[0];
    }

    // class name of initial implementation
    public function initial()
    {
        return array_values(array_slice($this->_getVersions(), 0))[0];
    }

    protected function _getVersions()
    {
        $versions = $this->versions();

        if (!is_array($versions)) {
            throw new ExternalZenatonException("'versions' method must return an array");
        }

        if (0 == count($versions)) {
            throw new ExternalZenatonException("'versions' method must return at least one element");
        }

        foreach ($versions as $key => $class) {
            if (!$this->isImplementationOf($class, WorkflowInterface::class)) {
                throw new ExternalZenatonException("Element returned by 'versions' method for key '".$key."' is not the name of a class implementing ".WorkflowInterface::class);
            }
        }

        return $versions;
    }
}
