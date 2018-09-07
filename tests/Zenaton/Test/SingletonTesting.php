<?php

namespace Zenaton\Test;

use Zenaton\Traits\SingletonTrait;

/**
 * Some utility methods to ease testing classes using singletons.
 */
trait SingletonTesting
{
    /**
     * Replaces a singleton instance with a mocked implementation.
     *
     * This allows you to alter behavior during testing and using mock assertions.
     *
     * @param string $classname The class name singleton to replace
     *
     * @return \PHPUnit_Framework_MockObject_MockObject A mock object corresponding to an instance of the given $classname parameter.
     */
    protected function replaceSingletonWithMock($classname)
    {
        static::ensureClassIsSingleton($classname);

        $instance = $classname::getInstance();
        $mock = $this->createMock($classname);

        $injector = function () use ($mock) {
            static::$instance = $mock;
        };

        $injector->call($instance);

        return $mock;
    }

    /**
     * Destroys the single instance of a given class.
     *
     * This makes sure your tests will get a new instance everytime, which will ensure no side effects
     * will happen between different tests.
     *
     * @param string $classname The class name singleton top destroy
     */
    protected static function destroySingleton($classname)
    {
        static::ensureClassIsSingleton($classname);

        $instance = $classname::getInstance();

        $injector = function () {
            static::$instance = null;
        };

        $injector->call($instance);
    }

    private static function ensureClassIsSingleton($classname)
    {
        $traits = class_uses($classname);
        // Class does not exist
        if ($traits === false) {
            throw new \UnexpectedValueException("Class \"{$classname}\" does not exist or cannot be autoloaded.");
        }
        if ($traits === [] || !in_array(SingletonTrait::class, $traits, true)) {
            throw new \UnexpectedValueException("Class \"{$classname}\" is not using \"Zenaton\Traits\SingletonTrait\"");
        }
    }
}
