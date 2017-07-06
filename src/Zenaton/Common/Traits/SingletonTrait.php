<?php

namespace Zenaton\Common\Traits;

trait SingletonTrait
{
    private static $instance = null;

    // __construct() is declared as protected to prevent creating a new instance outside of the class via the new operator.
    private function __construct()
    {
        if (method_exists($this, 'construct')) {
            return $this->construct();
        }
    }

    // __clone() is declared as protected to prevent creating a new instance outside of the class via the new operator.
    private function __clone()
    {
    }

    //  __wakeup() is declared as private to prevent unserializing of an instance of the class via the global function unserialize() .
    private function __wakeup()
    {
    }

    public static function getInstance()
    {
        if ( ! is_null(static::$instance)) {
            return static::$instance;
        }

        static::$instance = new static();

        return static::$instance;
    }

    public static function setInstance($o)
    {
        static::$instance = $o;
    }
}
