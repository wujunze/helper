<?php

namespace kuiper\helper;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;

/**
 * Access array use key separated by dot(.) :.
 *
 *     $array = new DotArray([
 *          'redis' => [
 *              'host' => 'localhost'
 *          ]
 *     ]);
 *     echo $array['redis.host'];   // 'localhost'
 */
class DotArray implements ArrayAccess, Iterator
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var \Generator
     */
    private $iterator;

    /**
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->data = $array;
    }

    /**
     * Gets the original array.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Merge data.
     *
     * @param array $array
     * @param bool  $deeply
     */
    public function merge(array $array, $deeply = true)
    {
        if ($deeply && !empty($this->data)) {
            $this->data = Arrays::merge($this->data, $array);
        } else {
            $this->data = array_merge($this->data, $array);
        }
    }

    /**
     * create flatten array.
     *
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isLeaf($key)
    {
        return strpos($key, '.') === false && strpos($key, '[') === false;
    }

    /**
     * call callback on each part of path.
     *
     *       $array = new DotArray([
     *           'redis' => [
     *                'servers' => [
     *                     ['host' => 'localhost']
     *                ]
     *           ]
     *       ]);
     *       $array->with('redis.servers[0].host', function($prefix, $value) {
     *       });
     *       // $prefix values:
     *       // redis
     *       // redis.servers
     *       // redis.servers[0]
     *       // redis.servers[0].host
     *
     * @param string   $offset
     * @param callable $callback
     */
    public function with($offset, callable $callback)
    {
        $path = $this->parsePath($offset);
        $value = $this->data;
        $parts = [];
        foreach ($path as $index) {
            if (!$this->hasKey($value, $index)) {
                break;
            }
            $parts[] = $index;
            $value = $value[$index];
            if ($callback($this->makePath($parts), $value) === false) {
                break;
            }
        }
    }

    protected function iterate($data, $prefix = null)
    {
        foreach ($data as $key => $value) {
            $path = $this->makePath([$prefix, $key]);
            if (is_array($value) || $value instanceof Iterator) {
                foreach ($this->iterate($value, $path) as $name => $item) {
                    yield $name => $item;
                }
            } else {
                yield $path => $value;
            }
        }
    }

    protected function makePath($parts)
    {
        $path = null;
        foreach ($parts as $index) {
            $path .= is_int($index) ? "[{$index}]" : ($path ? '.' : '').$index;
        }

        return $path;
    }

    protected function parsePath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('offset expects a string, got '.gettype($path));
        }
        $parts = [];
        foreach (explode('.', $path) as $part) {
            if (preg_match('/(?:\[\d+\])+/', $part, $matches)) {
                $indexes = $matches[0];
                $parts[] = substr($part, 0, -strlen($indexes));
                preg_match_all('/(?:\d+)/', $indexes, $matches);
                $parts = array_merge($parts, array_map('intval', $matches[0]));
            } else {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    protected function hasKey($array, $key)
    {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        } elseif ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        } else {
            return false;
        }
    }

    protected function isArray($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    protected function find($offset, $callback, $create = false)
    {
        $path = $this->parsePath($offset);
        $last = array_pop($path);
        $value = &$this->data;
        foreach ($path as $i => $index) {
            if ($this->hasKey($value, $index)) {
                $value = &$value[$index];
            } elseif ($create) {
                if (!$this->isArray($value)) {
                    throw new InvalidArgumentException(sprintf(
                        "unable to set value for '%s', value of '%s' is not array, got %s",
                        $offset,
                        $this->makePath(array_slice($path, 0, $i)),
                        gettype($value)
                    ));
                }
                $value[$index] = [];
                $value = &$value[$index];
            } else {
                return false;
            }
        }
        if ($create && !$this->isArray($value)) {
            throw new InvalidArgumentException(sprintf(
                "unable to set value for '%s', value of '%s' is not array, got %s",
                $offset,
                $this->makePath($path),
                gettype($value)
            ));
        }
        if ($create || $this->hasKey($value, $last)) {
            return $callback($value, $last);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->iterator = $this->iterate($this->data);
        $this->iterator->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->isLeaf($offset)) {
            return isset($this->data[$offset]) ? $this->data[$offset] : null;
        }
        $found = false;
        $value = $this->find($offset, function ($value, $key) use (&$found) {
            $found = true;

            return $value[$key];
        });

        return $found ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($this->isLeaf($offset)) {
            $this->data[$offset] = $value;
        } else {
            $this->find($offset, function (&$parent, $key) use ($value) {
                $parent[$key] = $value;
            }, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->isLeaf($offset)) {
            unset($this->data[$offset]);
        } else {
            $this->find($offset, function (&$value, $key) {
                unset($value[$key]);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if ($this->isLeaf($offset)) {
            return isset($this->data[$offset]);
        } else {
            return $this->find($offset, function ($value, $key) {
                return isset($value[$key]);
            });
        }
    }
}
