<?php

namespace Zenaton\Services;

use ReflectionClass;
use SuperClosure\Serializer as ClosureSerializer;
use Carbon\Carbon;
use UnexpectedValueException;
use Closure;

class Serializer
{
    // this string prefixs ids that are used to identify objects and Closure
    // it means that no
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
        $this->closure = new ClosureSerializer();
        $this->properties = new Properties();
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
            if ($data instanceof Closure) {
                $value[self::KEY_CLOSURE] = $this->encodeClosure($data);
            } else {
                $value[self::KEY_OBJECT] = $this->encodeObject($data);
            }
        } elseif (is_array($data)) {
            $value[self::KEY_ARRAY] = $this->encodeArray($data);
        } elseif (is_resource($data)) {
            $this->throwRessourceException();
        } else{
            $value[self::KEY_DATA] = $data;
        }
        //  $this->encoded may have been updated by encodeClosure or encodeObject
        $value[self::KEY_STORE] = $this->encoded;

        return json_encode($value);
    }

    public function decode($json)
    {
        $array = $this->jsonDecode($json);

        $this->decoded = [];
        $this->encoded = $array[self::KEY_STORE];

        if (array_key_exists(self::KEY_OBJECT, $array)) {
            $id = substr($array[self::KEY_OBJECT], strlen(self::ID_PREFIX));
            return $this->decodeObject($id, $this->encoded[$id]);
        }
        if (array_key_exists(self::KEY_CLOSURE, $array)) {
            $id = substr($array[self::KEY_CLOSURE], strlen(self::ID_PREFIX));
            return $this->decodeClosure($id, $this->encoded[$id]);
        }
        if (array_key_exists(self::KEY_ARRAY, $array)) {
            return $this->decodeArray($array[self::KEY_ARRAY]);
        }
        if (array_key_exists(self::KEY_DATA, $array)) {
            return $array[self::KEY_DATA];
        }
        throw new UnexpectedValueException('Unknown key in: '.$json);
    }

    protected function throwRessourceException()
    {
        throw new UnexpectedValueException('Ressources can not be serialized - use __sleep to clean and __wakeup to restore them');
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
            $this->encoded[$id][self::KEY_OBJECT_PROPERTIES] = $this->encodeArray($this->properties->getFromObject($o));
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
            } elseif (is_array($value)) {
                $array[$key] = $this->encodeArray($value);
            } elseif (is_resource($value)) {
                $this->throwRessourceException();
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

        // new empty instance
        $o = $this->properties->getNewInstanceWithoutProperties($encodedObject[self::KEY_OBJECT_NAME]);

        // Special case of Carbon object
        // Carbon's definition of __set method forbid direct set of 'date' parameter
        // while DateTime is still able to set them despite not declaring them!
        // it's a very special and messy case due to internal DateTime implementation
        if ($o instanceof Carbon) {
            $properties = $this->decodeArray($encodedObject[self::KEY_OBJECT_PROPERTIES]);
            $o = $this->properties->getNewInstanceWithoutProperties('DateTime');
            $dt = $this->properties->setToObject($o, $properties);
            // other possible implementation
            // $dt = 'O:8:"DateTime":3:{s:4:"date";s:' . strlen($properties['date']) . ':"' . $properties['date'] .
            //     '";s:13:"timezone_type";i:' . $properties['timezone_type'] .
            //     ';s:8:"timezone";s:'. strlen($properties['timezone']) . ':"' . $properties['timezone'] . '";}';
            // $dt = unserialize($dt);
            $o = Carbon::instance($dt);
            $this->decoded[$id] = $o;
            return $o;
        }

        // make sure this is in decoded array, before decoding properties, to avoid potential recursion
        $this->decoded[$id] = $o;

        // transpile properties
        $properties = $this->decodeArray($encodedObject[self::KEY_OBJECT_PROPERTIES]);

        // fill instance with properties
        return $this->properties->setToObject($o, $properties);
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
                    // object is define by an array [n =>, p=>]
                    $array[$key] = $this->decodeObject($id, $encoded);
                } else {
                    // if it's not an object, then it's a closure
                    $array[$key] = $this->decodeClosure($id, $encoded);
                }
            } elseif (is_array($value)) {
                $array[$key] = $this->decodeArray($value);
            }
        }
        return $array;
    }

    protected function jsonDecode($json, $asArray = true)
    {
        $result = json_decode($json, $asArray);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                throw new UnexpectedValueException('Maximum stack depth exceeded - ' . $json);
            case JSON_ERROR_STATE_MISMATCH:
                throw new UnexpectedValueException('Underflow or the modes mismatch - ' . $json);
            case JSON_ERROR_CTRL_CHAR:
                throw new UnexpectedValueException('Unexpected control character found - ' . $json);
            case JSON_ERROR_SYNTAX:
                throw new UnexpectedValueException('Syntax error, malformed JSON - ' . $json);
            case JSON_ERROR_UTF8:
                throw new UnexpectedValueException('Malformed UTF-8 characters, possibly incorrectly encoded - ' . $json);
            default:
                throw new UnexpectedValueException('Unknown error - ' . $json);
        }

        return $result;
    }
}
