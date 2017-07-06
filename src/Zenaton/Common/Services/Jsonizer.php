<?php

namespace Zenaton\Common\Services;

use Closure;
use ReflectionClass;
use SuperClosure\Serializer;
use Zenaton\Common\Exceptions\InternalZenatonException;

class Jsonizer
{
    const JSON_BREAKER = '#';

    protected $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer();
    }

    public function encode($data)
    {
        if (is_object($data)) {
            if ($data instanceof Closure) {
                $array = [
                    'closure' => $this->serializer->serialize($data),
                ];
            } else {
                $array = [
                    'class' => get_class($data),
                    'properties' => $this->getEncodedPropertiesFromObject($data),
                ];
            }
        } elseif (is_array($data)) {
            $array = [
                'array' => array_map([$this, 'encode'], $data),
            ];
        } else {
            $array = [
                'data' => $data,
            ];
        }

        return self::JSON_BREAKER.json_encode($array);
    }

    public function decode($json, $class = null)
    {
        $array = $this->jsonDecode(ltrim($json, self::JSON_BREAKER));

        if (isset($array['closure'])) {
            $object = $this->serializer->unserialize($array['closure']);
        } elseif (isset($array['class'])) {
            $object = $this->getObjectFromNameAndEncodedProperties($array['class'], $array['properties'], $class);
        } elseif (isset($array['array'])) {
            $object = array_map([$this, 'decode'], $array['array']);
        } else {
            $object = $array['data'];
        }

        return $object;
    }

    public function getEncodedPropertiesFromObject($o)
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

        // non-declared variables
        foreach ($o as $key => $value) {
            if ( ! isset($properties[$key])) {
                $properties[$key] = $value;
            }
        }

        return $this->encode($properties);
    }

    public function getObjectFromNameAndEncodedProperties($name, $encoded, $class = null)
    {
        $o = (new ReflectionClass($name))->newInstanceWithoutConstructor();

        // object must be of $class type
        if ( ! is_null($class) && ( ! is_object($o) || ! $o instanceof $class)) {
            throw new InternalZenatonException('Error - '.$name.' should be an instance of '.$class);
        }

        $properties = $this->decode($encoded);

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

    public function jsonDecode($json, $asArray = true)
    {
        $array = json_decode($json, $asArray);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
            break;
            case JSON_ERROR_DEPTH:
                echo $json.' - Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                echo $json.' - Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                echo $json.' - Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                echo $json.' - Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                echo $json.' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                echo $json.' - Unknown error';
            break;
        }

        return $array;
    }
}
