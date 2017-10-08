<?php

namespace Zenaton\Common\Services;

use ReflectionClass;
use SuperClosure\Serializer;
use Zenaton\Common\Exceptions\InternalZenatonException;

class Jsonizer
{
    const ID_PREFIX = "@zenaton#";

    const KEY_OBJECT = 'o';
    const KEY_OBJECT_NAME = 'n';
    const KEY_OBJECT_PROPERTIES = 'p';
    const KEY_ARRAY = 'a';
    const KEY_CLOSURE = 'c';
    const KEY_DATA = 'd';
    const KEY_STORE = 's';

    protected $closure;
    protected $encoded;
    protected $decoded;

    public function __construct()
    {
        $this->closure = new Serializer();
    }

    public function getEncodedPropertiesFromObject($o)
    {
        return $this->encode($this->getPropertiesFromObject($o));
    }

    public function getObjectFromNameAndEncodedProperties($name, $encodedProperties, $class = null)
    {
        $o = $this->getNewObject($name);

        // object must be of $class type
        if ( ! is_null($class) && ( ! is_object($o) || ! $o instanceof $class)) {
            throw new InternalZenatonException('Error - '.$name.' should be an instance of '.$class);
        }

        // decode properties
        $properties = $this->decode($encodedProperties);

        // fill empty object with properties
        return $this->setPropertiesToObject($o, $properties);
    }

    protected function getNewObject($name)
    {
        // this is a crazy hack necessary to be able to decode Carbon\Carbon object
        // Datetime has a date property created by its constructor
        // but Carbon forbid to access it if not yet set
        $params = (new ReflectionClass($name))->getConstructor()->getParameters();
        $useConstructor = count($params)===0 || array_unique(array_map(function($p) { return $p->isOptional(); }, $params)) === [true];

        if ($useConstructor) {
            $o = new $name;
            // this is necessary - I do not known why really
            var_export($o, true);

            return $o;
        }

        return (new ReflectionClass($name))->newInstanceWithoutConstructor();
    }

    public function setPropertiesToObject($o, $properties)
    {
        $r = new ReflectionClass($o);

        // declared variables
        $keys = [];
        foreach ($r->getProperties() as $property) {
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

    public function encode($data)
    {
        // $encoded array stores serialized version of objects found in $data
        // $decoded array stores objects found in $data
        // by having a unique reference of each object, we avoid infinite recursion
        // if $data includes some recursivity (eg. $a->child=$b; $b->parent=$a)
        $this->encoded = [];
        $this->decoded = [];

        if (is_object($data)) {
            if ($data instanceof \Closure) {
                $value[self::KEY_CLOSURE] = $this->encodeClosure($data);
            } else {
                $value[self::KEY_OBJECT] = $this->encodeObject($data);
            }
        } elseif (is_array($data)) {
            $value[self::KEY_ARRAY] = $this->encodeArray($data);
        } else {
            $value[self::KEY_DATA] = $data;
        }
        // this has been updated by encodeClosure or encodeObject
        $value[self::KEY_STORE] = $this->encoded;

        return json_encode($value);
    }

    public function decode($json)
    {
        $array = $this->jsonDecode($json);

        $this->decoded = [];
        $this->encoded = $array[self::KEY_STORE];

        if (array_key_exists(self::KEY_CLOSURE, $array)) {
            $id = substr($array[self::KEY_CLOSURE], strlen(self::ID_PREFIX));
            return $this->decodeClosure($id, $this->encoded[$id]);
        }
        if (array_key_exists(self::KEY_OBJECT, $array)) {
            $id = substr($array[self::KEY_OBJECT], strlen(self::ID_PREFIX));
            return $this->decodeObject($id, $this->encoded[$id]);
        }
        if (array_key_exists(self::KEY_ARRAY, $array)) {
            return $this->decodeArray($array[self::KEY_ARRAY]);
        }
        if (array_key_exists(self::KEY_DATA, $array)) {
            return $array[self::KEY_DATA];
        }
        throw new InternalZenatonException('Unknown key in: '.$json);
    }

    protected function isObjectId($s)
    {
        $len = strlen(self::ID_PREFIX);

        return is_string($s)
            && substr($s, 0, $len) === self::ID_PREFIX
            && in_array(substr($s, $len), array_keys($this->encoded));
    }

    protected function encodeObject($o)
    {
        // get key of existing object
        $id = array_search($o, $this->decoded, true);

        // store object in encoded array if not yet present
        if ($id === false) {
            $id = count($this->decoded);
            $this->decoded[$id] = $o;
            $this->encoded[$id][self::KEY_OBJECT_NAME] = get_class($o);
            $this->encoded[$id][self::KEY_OBJECT_PROPERTIES] = $this->encodeArray($this->getPropertiesFromObject($o));
        }

        return self::ID_PREFIX . $id;
    }

    protected function encodeClosure($c)
    {
        // get key of existing object
        $id = array_search($c, $this->decoded, true);

        // store object in encoded array if not yet present
        if ($id === false) {
            $id = count($this->decoded);
            $this->decoded[$id] = $c;
            $this->encoded[$id] = $this->closure->serialize($c);
        }

        return self::ID_PREFIX . $id;
    }

    protected function encodeArray($a)
    {
        $array = [];
        foreach ($a as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof \Closure) {
                    $array[$key] =  $this->encodeClosure($value);
                } else {
                    $array[$key] =  $this->encodeObject($value);
                }
            } else if (is_array($value)) {
                $array[$key] = $this->encodeArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    protected function decodeObject($id, $encodedObject) {
        // return object if already known (avoid recursion)
        if (in_array($id, array_keys($this->decoded))) {
            return $this->decoded[$id];
        }

        // build object
        $object = $this->getNewObject($encodedObject[self::KEY_OBJECT_NAME]);

        $this->decoded[$id] = $object;

        // transpile properties
        $properties = $this->decodeArray($encodedObject[self::KEY_OBJECT_PROPERTIES]);

        return $this->setPropertiesToObject($object, $properties);
    }

    protected function decodeClosure($id, $encodedClosure) {
        // return object if already known (avoid recursion)
        if (in_array($id, array_keys($this->decoded))) {
            return $this->decoded[$id];
        }

        // build closure
        $closure = $this->closure->unserialize($encodedClosure);
        $this->decoded[$id] = $closure;

        return $closure;
    }

    protected function decodeArray($array) {
        foreach ($array as $key => $value) {
            if ($this->isObjectId($value)) {
                $id = substr($value, strlen(self::ID_PREFIX));
                $encoded = $this->encoded[$id];
                if (is_array($encoded)) {
                    $array[$key] = $this->decodeObject($id, $encoded);
                } else {
                    $array[$key] = $this->decodeClosure($id, $encoded);
                }
            } elseif (is_array($value)) {
                $array[$key] = $this->decodeArray($value);
            }
        }
        return $array;
    }

    protected function getPropertiesFromObject($o)
    {
        // why cloning ? https://divinglaravel.com/queue-system/preparing-jobs-for-queue
        $clone = clone $o;
        // apply __sleep before serialization - should return an array of properties to be serialized
        if (method_exists($clone, '__sleep')) {
            $valid = $clone->__sleep() ? : [];
        }

        $r = new ReflectionClass($clone);

        $properties = [];

        // declared variables
        foreach ($r->getProperties() as $property) {
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

    protected function jsonDecode($json, $asArray = true)
    {
        $result = json_decode($json, $asArray);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                throw new InternalZenatonException('Maximum stack depth exceeded - ' . $json);
            case JSON_ERROR_STATE_MISMATCH:
                throw new InternalZenatonException('Underflow or the modes mismatch - ' . $json);
            case JSON_ERROR_CTRL_CHAR:
                throw new InternalZenatonException('Unexpected control character found - ' . $json);
            case JSON_ERROR_SYNTAX:
                throw new InternalZenatonException('Syntax error, malformed JSON - ' . $json);
            case JSON_ERROR_UTF8:
                throw new InternalZenatonException('Malformed UTF-8 characters, possibly incorrectly encoded - ' . $json);
            default:
                throw new InternalZenatonException('Unknown error - ' . $json);
        }

        return $result;
    }
}
