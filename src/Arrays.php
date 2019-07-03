<?php

namespace kuiper\helper;

use ArrayAccess;
use InvalidArgumentException;
use ReflectionClass;

class Arrays
{
    /**
     * Gets object field value by getter method.
     */
    const GETTER = 'getter';

    /**
     * Gets object field value by property.
     */
    const OBJ = 'obj';

    /**
     * Gets value from array by key.
     *
     * @param ArrayAccess|array $arr
     * @param string            $key
     * @param mixed             $default
     *
     * @return mixed
     */
    public static function fetch($arr, $key, $default = null)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Collects value from array.
     *
     * @param array|\Iterator $arr
     * @param string          $name
     * @param string          $type
     *
     * @return array
     */
    public static function pull($arr, $name, $type = null)
    {
        $ret = [];
        if ($type == self::GETTER) {
            $method = 'get'.$name;
            foreach ($arr as $elem) {
                $ret[] = $elem->$method();
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[] = $elem->$name;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[] = $elem[$name];
            }
        }

        return $ret;
    }

    /**
     * Creates associated array.
     *
     * @param array|\Iterator $arr
     * @param string          $name
     * @param string          $type
     *
     * @return array
     */
    public static function assoc($arr, $name, $type = null)
    {
        $ret = [];
        if (empty($arr)) {
            return $ret;
        }
        if ($type == self::GETTER) {
            $method = 'get'.$name;
            foreach ($arr as $elem) {
                $ret[$elem->$method()] = $elem;
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[$elem->$name] = $elem;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[$elem[$name]] = $elem;
            }
        }

        return $ret;
    }

    /**
     * Excludes key in given keys.
     *
     * @param array $arr
     * @param array $excludedKeys
     *
     * @return array
     */
    public static function exclude(array $arr, array $excludedKeys)
    {
        return array_diff_key($arr, array_flip($excludedKeys));
    }

    /**
     * Changes key name.
     *
     * @param array $arr
     * @param array $columnMap
     *
     * @return array
     */
    public static function rename(array $arr, array $columnMap)
    {
        $ret = $arr;
        foreach ($columnMap as $key => $newKey) {
            if (array_key_exists($key, $arr)) {
                unset($ret[$key]);
                $ret[$newKey] = $arr[$key];
            }
        }

        return $ret;
    }

    /**
     * Create array with given keys.
     *
     * @param array  $arr
     * @param array  $includedKeys
     * @param string $type
     *
     * @return array
     */
    public static function select($arr, array $includedKeys, $type = null)
    {
        $ret = [];
        if ($type == self::GETTER) {
            foreach ($includedKeys as $name) {
                $method = 'get'.$name;
                if (method_exists($arr, $method)) {
                    $ret[$name] = $arr->$method();
                }
            }
        } elseif ($type == self::OBJ) {
            foreach ($includedKeys as $name) {
                if (isset($arr->$name)) {
                    $ret[$name] = $arr->$name;
                }
            }
        } else {
            if ($arr instanceof ArrayAccess) {
                foreach ($includedKeys as $key) {
                    if ($arr->offsetExists($key)) {
                        $ret[$key] = $arr[$key];
                    }
                }
            } else {
                foreach ($includedKeys as $key) {
                    if (array_key_exists($key, $arr)) {
                        $ret[$key] = $arr[$key];
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Filter null value.
     *
     * @param array $arr
     *
     * @return array
     */
    public static function filter(array $arr)
    {
        return array_filter($arr, function ($elem) {
            return isset($elem);
        });
    }

    /**
     * @param object          $bean
     * @param array|\Iterator $attributes
     * @param bool            $onlyPublic
     *
     * @return object
     */
    public static function assign($bean, $attributes, $onlyPublic = true)
    {
        if ($bean === null || !is_object($bean)) {
            throw new InvalidArgumentException("Parameter 'bean' need be an object, got ".gettype($bean));
        }
        if ($bean instanceof ArrayAccess) {
            foreach ($attributes as $name => $val) {
                $bean[$name] = $val;
            }
        } else {
            $properties = get_object_vars($bean);
            foreach ($attributes as $name => $val) {
                if (array_key_exists($name, $properties)) {
                    $bean->{$name} = $val;
                } elseif (method_exists($bean, $method = 'set'.Text::camelize($name))) {
                    $bean->$method($val);
                } elseif (!$onlyPublic) {
                    $failed[$name] = $val;
                }
            }
            if (!$onlyPublic && !empty($failed)) {
                $class = new ReflectionClass($bean);
                foreach ($failed as $name => $val) {
                    if (!$class->hasProperty($name)) {
                        continue;
                    }
                    $property = $class->getProperty($name);
                    if ($property) {
                        $property->setAccessible(true);
                        $property->setValue($bean, $val);
                    }
                }
            }
        }

        return $bean;
    }

    /**
     * @param object $bean
     * @param bool   $includeGetters
     * @param bool   $uncamelizeKey
     * @param bool   $recursive
     *
     * @return array
     */
    public static function toArray($bean, $includeGetters = true, $uncamelizeKey = false, $recursive = false)
    {
        if ($bean === null || !is_object($bean)) {
            throw new \InvalidArgumentException("Parameter 'bean' need be an object, got ".gettype($bean));
        }
        $properties = get_object_vars($bean);
        if ($includeGetters) {
            $class = new \ReflectionClass($bean);
            foreach ($class->getMethods() as $method) {
                if ($method->isStatic() || !$method->isPublic()) {
                    continue;
                }
                if (preg_match('/^(get|is|has)(.+)/', $method->getName(), $matches)
                    && $method->getNumberOfParameters() === 0) {
                    $properties[lcfirst($matches[2])] = $method->invoke($bean);
                }
            }
        }
        if ($uncamelizeKey) {
            $properties = self::uncamelizeKeys($properties);
        }
        if ($recursive) {
            $properties = self::recursiveToArray($properties, $includeGetters, $uncamelizeKey);
        }

        return $properties;
    }

    private static function recursiveToArray(array $values, $includeGetters, $uncamelizeKey)
    {
        foreach ($values as $key => $val) {
            if (is_object($val)) {
                $values[$key] = self::toArray($val, $includeGetters, $uncamelizeKey, true);
            } elseif (is_array($val)) {
                $values[$key] = self::recursiveToArray($val, $includeGetters, $uncamelizeKey);
            }
        }

        return $values;
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     *
     * Borrow from yii\helper\ArrayHelper
     *
     * @param array $args arrays to be merged
     *
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge(...$args)
    {
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * create sorter.
     *
     * @param string   $name field name
     * @param callable $func comparator
     * @param string   $type
     *
     * @return callable
     */
    public static function sorter($name, $func = null, $type = null)
    {
        if (!isset($func)) {
            $func = function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }

                return $a < $b ? -1 : 1;
            };
        }

        if ($type == self::GETTER) {
            $method = 'get'.$name;

            return function ($a, $b) use ($method, $func) {
                return call_user_func($func, $a->$method(), $b->$method());
            };
        } elseif ($type == self::OBJ) {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a->$name, $b->$name);
            };
        } else {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a[$name], $b[$name]);
            };
        }
    }

    /**
     * Applies the callback to the keys of given array.
     *
     * @param array    $arr
     * @param callable $callback
     *
     * @return array
     */
    public static function mapKeys(array $arr, callable $callback)
    {
        $result = [];
        array_walk($arr, function (&$value, $key) use (&$result, $callback) {
            $result[$callback($key)] = $value;
        });

        return $result;
    }

    public static function uncamelizeKeys(array $arr)
    {
        return self::mapKeys($arr, [Text::class, 'uncamelize']);
    }
}
