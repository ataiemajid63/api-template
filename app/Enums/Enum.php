<?php

namespace App\Enums;

abstract class Enum
{
    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getList()
    {
        $class = new \ReflectionClass(static::class);

        $list = $class->getConstants();
        array_unshift($list, '');

        return $list;
    }

    /**
     * @param integer|string $key
     * @return string|null
     * @throws \ReflectionException
     */
    public static function getValue($key)
    {
        $list = static::getList();
        $keys = array_keys($list);
        $key = is_numeric($key) ? (integer)$key : $key;
        $value = null;

        if (is_integer($key) && $key < count($keys)) {
            $value = $list[$keys[$key]];
        } else if(!is_null($key)) {
            $value = $list[strtoupper($key)];
        }

        return $value;
    }

    /**
     * @param integer|string $value
     * @return string|null
     * @throws \ReflectionException
     */
    public static function getKey($value)
    {
        $list = static::getList();
        $keys = array_keys($list);
        $values = array_values($list);
        $index = array_search($value, $values);

        $key = $keys[$index] ?? null;

        return $key;
    }

    /**
     * @param mixed $value
     * @return integer
     * @throws \ReflectionException
     */
    public static function indexOf($value)
    {
        $index = null;

        if(!is_null($value)) {
            $list = static::getList();
            $values = array_values($list);
            $index = array_search($value, $values);
        }

        return $index;
    }
}
