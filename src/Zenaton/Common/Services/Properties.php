<?php

namespace Zenaton\Common\Services;

use ReflectionClass;
use Carbon\Carbon;

class Properties
{
    public function getNewInstanceWithoutProperties($name)
    {
        return (new ReflectionClass($name))->newInstanceWithoutConstructor();
    }

    public function getObjectFromNameAndProperties($name, $properties, $class = null)
    {
        $o = $this->getNewInstanceWithoutProperties($name);

        // object must be of $class type
        if ( ! is_null($class) && ( ! is_object($o) || ! $o instanceof $class)) {
            throw new InternalZenatonException('Error - '.$name.' should be an instance of '.$class);
        }

        // fill empty object with properties
        return $this->setToObject($o, $properties);
    }

    public function getFromObject($o)
    {
        // why cloning ? https://divinglaravel.com/queue-system/preparing-jobs-for-queue
        $clone = clone $o;

        // apply __sleep before serialization - should return an array of properties to be serialized
        if (method_exists($clone, '__sleep')) {
            $valid = $clone->__sleep() ? : [];
        }

        $properties = [];

        // declared variables
        foreach ((new ReflectionClass($clone))->getProperties() as $property) {
            // the PHP serialize method doesn't take static variables so we respect this philosophy
            if (! $property->isStatic() && (! isset($valid) || in_array($property->getName(), $valid))) {
                $property->setAccessible(true);
                $value = $property->getValue($clone);
                $properties[$property->getName()] = $value;
           }
        }

        # non-declared public variables
        foreach ($clone as $key => $value) {
            if (! isset($properties[$key]) && (! isset($valid) || in_array($key, $valid))) {
                $properties[$key] = $value;
            }
        }

        return $properties;
    }

    public function setToObject($o, $properties)
    {
        // Special case of Carbon object
        // Carbon __set forbid direct set of 'date' parameter
        // while DateTime is still able to set them despite not declaring them!
        // it's a very special and messy case
        if ($o instanceof Carbon) {
            $o = $this->getNewInstanceWithoutProperties('DateTime');
            $dt = $this->setToObject($o, $properties);
            return Carbon::instance($dt);
        }

        // declared variables
        $keys = [];
        foreach ((new ReflectionClass($o))->getProperties() as $property) {
            // the PHP serialize method doesn't take static variables so we respect this philosophy
            if (! $property->isStatic()) {
                $property->setAccessible(true);
                $key = $property->getName();
                $property->setValue($o, $properties[$key]);
                $keys[] = $key;
            }
        }

        // non-declared variables
        foreach ($properties as $key => $value) {
            if ( ! in_array($key, $keys)) {
                $o->{$key} = $value;
            }
        }

        // we now have the complete object, time to wake up
        if (method_exists($o, '__wakeup')) {
            $o->__wakeup();
        }

        return $o;
    }
}
