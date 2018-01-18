<?php

namespace Zenaton\v2\Workflows;

use ReflectionClass;

use Zenaton\Exceptions\ExternalZenatonException;

use Zenaton\v2\Interfaces\WorkflowInterface;
use Zenaton\v2\Interfaces\VersionInterface;
use Zenaton\v2\Traits\Zenatonable;
use Zenaton\v2\Traits\IsImplementationOfTrait;

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
        return ($this->getCurrentImplementation())->handle();
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

        if (! is_array($versions)) {
            throw new ExternalZenatonException("'versions' method must return an array");
        }

        if (count($versions) == 0) {
            throw new ExternalZenatonException("'versions' method must return at least one element");
        }

        foreach ($versions as $key => $class) {
            if (! $this->isImplementationOf($class, WorkflowInterface::class)) {
                throw new ExternalZenatonException("Element returned by 'versions' method for key '" . $key . "' is not the name of a class implementing " . WorkflowInterface::class);
            }
        }

        return $versions;
    }
}
