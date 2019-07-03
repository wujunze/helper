<?php

namespace kuiper\helper;

use InvalidArgumentException;
use ReflectionClass;

/**
 * enum class.
 *
 * @property string name
 * @property mixed value
 */
abstract class Enum implements \JsonSerializable
{
    /**
     * key = className
     * value = array which key is enum value.
     */
    private static $VALUES = [];

    /**
     * key = className
     * value = array which key is enum name.
     */
    private static $NAMES = [];

    /**
     * properties for enum instances.
     */
    protected static $PROPERTIES = [];

    /**
     * @var string name of enum
     */
    protected $name;

    /**
     * @var mixed value of enum
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $name
     * @param mixed  $value
     */
    protected function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Gets name of enum instance.
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Gets value of enum instance.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * default to string method.
     *
     * @return string name of enum
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Gets properties.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset(static::$PROPERTIES[$name])) {
            return isset(static::$PROPERTIES[$name][$this->value]) ? static::$PROPERTIES[$name][$this->value] : null;
        } elseif (property_exists($this, $name)) {
            return $this->$name;
        } else {
            throw new \InvalidArgumentException('Undefined property: '.get_class($this).'::$'.$name);
        }
    }

    public function __isset($name)
    {
        return isset(static::$PROPERTIES[$name][$this->value])
            || isset($this->$name);
    }

    /**
     * Gets all enum values.
     *
     * @return array
     */
    public static function values()
    {
        return array_keys(static::getValues());
    }

    /**
     * Gets all enum names.
     *
     * @return array
     */
    public static function names()
    {
        return array_keys(static::getNames());
    }

    /**
     * Gets all enum intval.
     *
     * @return array
     */
    public static function intvals()
    {
        if (empty(static::$PROPERTIES['intval'])) {
            throw new \RuntimeException("property 'intval' is not defined, please set value for ".get_called_class().'::$PROPERTIES["intval"]');
        }

        return array_values(static::$PROPERTIES['intval']);
    }

    /**
     * Gets all enum ordinals.
     *
     * @return array
     *
     * @deprecated use intvals instead
     */
    public static function ordinals()
    {
        if (empty(static::$PROPERTIES['ordinal'])) {
            throw new \RuntimeException("property 'ordinal' is not defined, please set value for ".get_called_class().'::$PROPERTIES["ordinal"]');
        }

        return array_values(static::$PROPERTIES['ordinal']);
    }

    /**
     * Gets all enums.
     *
     * @return static[]
     */
    public static function instances()
    {
        return array_values(self::getValues());
    }

    /**
     * Checks whether the enum value exists.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function hasValue($value)
    {
        return array_key_exists($value, static::getValues());
    }

    /**
     * Gets the name for the enum value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function nameOf($value)
    {
        $values = static::getValues();

        return isset($values[$value]) ? $values[$value]->name() : null;
    }

    /**
     * Checks whether the name of enum value exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function hasName($name)
    {
        return array_key_exists($name, static::getNames());
    }

    /**
     * Gets the enum value for the name.
     *
     * @param string $name
     *
     * @return mixed value of
     */
    public static function valueOf($name)
    {
        $names = static::getNames();

        return isset($names[$name]) ? $names[$name] : null;
    }

    /**
     * Gets the enum instance for the name.
     *
     * @param string $name
     * @param static $default
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function fromName($name, $default = null)
    {
        $names = static::getNames();
        if (array_key_exists($name, $names)) {
            return self::fromValue($names[$name]);
        }
        if ($default === null) {
            throw new InvalidArgumentException("No enum constant '$name' in class ".get_called_class());
        }

        return $default;
    }

    /**
     * Gets the enum instance for the value.
     *
     * @param mixed  $value
     * @param static $default
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function fromValue($value, $default = null)
    {
        $values = static::getValues();
        if (array_key_exists($value, $values)) {
            return $values[$value];
        }
        if ($default === null) {
            throw new InvalidArgumentException("No enum constant value '$value' class ".get_called_class());
        }

        return $default;
    }

    /**
     * Gets the enum instance match properties intval.
     *
     * @param int    $intval
     * @param static $default
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function fromIntval($intval, $default = null)
    {
        if (empty(static::$PROPERTIES['intval'])) {
            throw new \RuntimeException("property 'intval' is not defined, please set value for ".get_called_class().'::$PROPERTIES["intval"]');
        }
        $value = array_search($intval, static::$PROPERTIES['intval']);
        if ($value !== false) {
            return self::fromValue($value);
        } else {
            if ($default === null) {
                throw new InvalidArgumentException("No enum intval for '$intval' class ".get_called_class());
            }

            return $default;
        }
    }

    /**
     * Gets the enum instance match properties intval.
     *
     * @param int    $intval
     * @param static $default
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function fromInt($intval, $default = null)
    {
        return self::fromIntval($intval, $default);
    }

    /**
     * Gets the enum instance match properties ordinal.
     *
     * @param int    $ordinal
     * @param static $default
     *
     * @return static
     *
     * @deprecated use fromIntval
     */
    public static function fromOrdinal($ordinal, $default = null)
    {
        if (empty(static::$PROPERTIES['ordinal'])) {
            throw new \RuntimeException("property 'ordinal' is not defined, please set value for ".get_called_class().'::$PROPERTIES["ordinal"]');
        }
        $value = array_search($ordinal, static::$PROPERTIES['ordinal']);
        if ($value !== false) {
            return self::fromValue($value);
        } else {
            if ($default === null) {
                throw new InvalidArgumentException("No enum ordinal for '$ordinal' class ".get_called_class());
            }

            return $default;
        }
    }

    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE() given SOME_VALUE is a class constant.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic($name, $arguments)
    {
        if (static::hasName($name)) {
            return static::fromName($name);
        } else {
            throw new \BadMethodCallException("unknown method '$name'");
        }
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    /**
     * @return static[]
     */
    protected static function getValues()
    {
        $class = get_called_class();
        if (!array_key_exists($class, self::$VALUES)) {
            $reflect = new ReflectionClass($class);
            $constants = $reflect->getConstants();
            self::$NAMES[$class] = $constants;
            $flip = [];
            foreach ($constants as $name => $val) {
                $flip[$val] = new $class($name, $val);
            }
            // Should not use `array_flip` here, error will be triggered if value is true or false
            // array_flip(): Can only flip STRING and INTEGER values! on line 1
            self::$VALUES[$class] = $flip;
        }

        return self::$VALUES[$class];
    }

    protected static function getNames()
    {
        $class = get_called_class();
        if (!isset(self::$NAMES[$class])) {
            static::getValues();
        }

        return self::$NAMES[$class];
    }
}
