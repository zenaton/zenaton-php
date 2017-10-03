<?php

namespace Zenaton\Common\Services;

use Closure;
use ReflectionClass;
use SuperClosure\Serializer;
use Zenaton\Common\Exceptions\InternalZenatonException;

class Jsonizer
{
    const ID_PREFIX = "@zenaton#";

    protected $serializer;
    protected $encoded;
    protected $decoded;

    public function __construct()
    {
        $this->serializer = new Serializer();
    }

    public function getEncodedPropertiesFromObject($o)
    {
        return $this->encode($this->getPropertiesFromObject($o));
    }

    public function getObjectFromNameAndEncodedProperties($name, $encoded, $class = null)
    {
        $o = (new ReflectionClass($name))->newInstanceWithoutConstructor();

        // object must be of $class type
        if ( ! is_null($class) && ( ! is_object($o) || ! $o instanceof $class)) {
            throw new InternalZenatonException('Error - '.$name.' should be an instance of '.$class);
        }

        // decode properties
        $properties = $this->decode($encoded);

        // fill empty object with properties
        return $this->setPropertiesToObject($o, $properties);
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

        return $o;
    }

    public function encode($data)
    {
        // $encoded array stores serialized version of objects found in $data
        // $decoded array stores objects found in $data
        // by having a unique reference of each object, we avoid infinite
        // looping if $data includes some recursivity (eg. $a->child=$b; $b->parent=$a)
        $this->encoded = [];
        $this->decoded = [];

        if (is_object($data)) {
            // if ($data instanceof Closure) {
                    // closure
            //     $this->encoded['c'] = $this->serializer->serialize($data);
            // } else {
                // object
                $value['o'] = $this->encodeObject($data);
            // }
        } elseif (is_array($data)) {
            // array
            $value['a'] = $this->encodeArray($data);
        } else {
            // data
            $value['d'] = $data;
        }
        // store of objects
        $value['s'] = $this->encoded;

        return json_encode($value);
    }

    public function decode($json)
    {
        $array = $this->jsonDecode($json);

        $this->decoded = [];
        $this->encoded = $array['s'];

        if (isset($array['d'])) {
            return $array['d'];
        }
        if (isset($array['o'])) {
            $id = substr($array['o'], strlen(self::ID_PREFIX));
            $encoded = $this->encoded[$id];
            return $this->decodeObject($id, $encoded['n'], $encoded['p']);
        }
        if (isset($array['a'])) {
            return $this->decodeArray($array['a']);
        }
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
            $this->encoded[$id]['n'] = get_class($o);
            $this->encoded[$id]['p'] = $this->encodeArray($this->getPropertiesFromObject($o));
        }

        return self::ID_PREFIX . $id;
    }

    protected function encodeArray($a)
    {
        $array = [];
        foreach ($a as $key => $value) {
            if (is_object($value)) {
                $array[$key] = $this->encodeObject($value);
            } else if (is_array($value)) {
                $array[$key] = $this->encodeArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    protected function decodeObject($id, $name, $properties) {
        // return object if already known (avoid recursion)
        if (in_array($id, array_keys($this->decoded))) {
            return $this->decoded[$id];
        }

        // build object
        $object = (new ReflectionClass($name))->newInstanceWithoutConstructor();
        $this->decoded[$id] = $object;

        // transpile properties
        $properties = $this->decodeArray($properties);

        return $this->setPropertiesToObject($object, $properties);
    }

    protected function decodeArray($array) {
        foreach ($array as $key => $value) {
            if ($this->isObjectId($value)) {
                $id = substr($value, strlen(self::ID_PREFIX));
                $array[$key] = $this->decodeObject($id, $this->encoded[$id]['n'], $this->encoded[$id]['p']);
            } elseif (is_array($value)) {
                $array[$key] = $this->decodeArray($value);
            }
        }
        return $array;
    }

    protected function getPropertiesFromObject($o)
    {
        $r = new ReflectionClass($o);

        $properties = [];

        // declared variables
        foreach ($r->getProperties() as $property) {
            // the PHP serialize method doesn't take static variables so we respect this philosophy
            if (! $property->isStatic()) {
                $property->setAccessible(true);
                $value = $property->getValue($o);
                $properties[$property->getName()] = $value;
           }
        }

        # non-declared public variables
        foreach ($o as $key => $value) {
            if ( ! isset($properties[$key])) {
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
