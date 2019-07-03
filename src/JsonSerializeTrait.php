<?php

namespace kuiper\helper;

/**
 * Helper class for implements JsonSerializable.
 */
trait JsonSerializeTrait
{
    public function jsonSerialize()
    {
        return self::toArray();
    }

    public function toArray($keyStyle = null, $excludedKeys = [])
    {
        $vars = get_object_vars($this);
        if (isset($keyStyle)) {
            if ($keyStyle === 'camelize') {
                $vars = Arrays::mapKeys($vars, function ($key) {
                    return lcfirst(Text::camelize($key));
                });
            } elseif ($keyStyle === 'uncamelize') {
                $vars = Arrays::mapKeys($vars, [Text::class, 'uncamelize']);
            }
        }
        if (!empty($excludedKeys)) {
            foreach ($excludedKeys as $key) {
                unset($vars[$key]);
            }
        }

        return $vars;
    }
}
