<?php

namespace Zenaton\Services;

use ReflectionClass;
use UnexpectedValueException;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;

class Properties
{
    /**
     * Property names that are not allowed to be serialized for workflows and tasks.
     *
     * @var string[]
     */
    const EXCLUDED_PROPERTIES = ['context'];

    public function getNewInstanceWithoutProperties($name)
    {
        return (new ReflectionClass($name))->newInstanceWithoutConstructor();
    }

    public function getObjectFromNameAndProperties($name, $properties, $class = null)
    {
        $o = $this->getNewInstanceWithoutProperties($name);

        // object must be of $class type
        $this->checkClass($o, $class);

        // fill empty object with properties
        return $this->setPropertiesToObject($o, $properties);
    }

    public function getPropertiesFromObject($o)
    {
        // why cloning ? https://divinglaravel.com/queue-system/preparing-jobs-for-queue
        $reflectionObject = new \ReflectionObject($o);
        $clone = $reflectionObject->isCloneable() ? clone $o : $o;

        // apply __sleep before serialization - should return an array of properties to be serialized
        if (method_exists($clone, '__sleep')) {
            $valid = $clone->__sleep() ?: [];
        }

        $properties = [];

        // declared variables
        foreach ($this->getClassProperties($clone) as $property) {
            if (
                // the PHP serialize method doesn't take static variables so we respect this philosophy
                !$property->isStatic()
                // if $valid is not set we serialize everything. if $valid is defined and contains the property name it can be serialized in respect to the __sleep method implementation
                && (!isset($valid) || in_array($property->getName(), $valid, true))
                // if property name is inside the list of excluded properties, we don't serialize it
                && !in_array($property->getName(), $this->getExcludedPropertiesForObject($clone), true)
            ) {
                $property->setAccessible(true);
                $value = $property->getValue($clone);
                $properties[$property->getName()] = $value;
            }
        }

        // non-declared public variables. Only if object does not implement \Traversable, because otherwise it can iterate things that are not properties
        if (!($clone instanceof \Traversable)) {
            foreach ($clone as $key => $value) {
                if (!isset($properties[$key]) && (!isset($valid) || in_array($key, $valid))) {
                    $properties[$key] = $value;
                }
            }
        }

        return $properties;
    }

    public function setPropertiesToObject($o, $properties)
    {
        // declared variables
        $keys = [];
        foreach ($this->getClassProperties($o) as $property) {
            // the PHP serialize method doesn't take static variables so we respect this philosophy
            if (!$property->isStatic()) {
                $property->setAccessible(true);
                $key = $property->getName();
                // check if $key exist in properties
                // eg. keys not in _sleep() (if any) won't be here
                if (isset($properties[$key])) {
                    $property->setValue($o, $properties[$key]);
                }
                $keys[] = $key;
            }
        }

        // non-declared variables
        foreach ($properties as $key => $value) {
            if (!in_array($key, $keys)) {
                $o->{$key} = $value;
            }
        }

        // we now have the complete object, time to wake up
        if (method_exists($o, '__wakeup')) {
            $o->__wakeup();
        }

        return $o;
    }

    protected function checkClass($o, $class)
    {
        // object must be of $class type
        if (!is_null($class) && (!is_object($o) || !$o instanceof $class)) {
            throw new UnexpectedValueException('Error - '.get_class($o).' should be an instance of '.$class);
        }
    }

    /**
     * Return properties of a class and all its inheritance hierarchy.
     *
     * @param object|string $argument Object or class name to get properties from
     * @param null|int      $filter   Optional filter, for filtering desired property types. It's configured using the `\ReflectionProperty` constants, and defaults to all property types.
     *
     * @throws \ReflectionException if the class to reflect does not exist
     *
     * @return \ReflectionProperty[]
     */
    private function getClassProperties($argument, $filter = null)
    {
        if (null === $filter) {
            $filter = \ReflectionProperty::IS_STATIC | \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE;
        }

        $reflectionClass = new \ReflectionClass($argument);

        if ($parentClass = $reflectionClass->getParentClass()) {
            return array_merge($this->getClassProperties($parentClass->getName(), $filter), $reflectionClass->getProperties($filter));
        }

        return $reflectionClass->getProperties($filter);
    }

    /**
     * Returns the list of properties to exclude from properties extraction for a given object.
     *
     * @param mixed $o
     *
     * @return string[]
     */
    private function getExcludedPropertiesForObject($o)
    {
        if ($o instanceof WorkflowInterface || $o instanceof TaskInterface) {
            return static::EXCLUDED_PROPERTIES;
        }

        return [];
    }
}
