<?php

namespace App\Enums;

abstract class Bitwise
{
    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getList()
    {
        $class = new \ReflectionClass(static::class);

        $list = $class->getConstants();

        return $list;
    }

    /**
     * @param string|array|integer $keys
     * @return integer|null
     * @throws \ReflectionException
     */
    public static function getValue($keys)
    {
        $list = static::getList();
        $keysList = array_keys($list);
        $value = null;

        if (is_array($keys) && count($keys)) {
            $value = [];

            foreach ($keys as $key) {
                $value[] = array_search(strtoupper($key), $keysList);
            }

            asort($value);
            $value = array_values($value);
            $bitsLength = $value[count($value) - 1] + 1;
            $bits = array_fill(0, $bitsLength, 0);

            for ($i = 0; $i <= $bitsLength; $i++) {
                if (in_array($i, $value)) {
                    $bits[$i] = 1;
                }
            }

            $value = (int)base_convert(implode('', array_reverse($bits)), 2, 10);

        } else if (is_string($keys) && $keys !== '') {
            $value = (int)$list[strtoupper($keys)];
        }

        return $value;
    }

    /**
     * @param integer $value
     * @return array|string
     * @throws \ReflectionException
     */
    public static function getName($value)
    {
        $list = static::getList();
        $keys = array_keys($list);
        $bin = base_convert($value, 10, 2);
        $binary = str_split($bin);
        $result = [];

        if(!empty($binary) && is_array($binary)) {
            $binary = array_reverse($binary);
        }

        foreach ($binary as $index => $bit) {
            if($bit == 1 && isset($keys[$index])) {
                $result[] = strtolower($keys[$index]);
            }
        }

        return count($result) === 1 ? $result[0] : $result;
    }

    /**
     * @param integer $value
     * @param integer $source
     * @return boolean
     */
    public static function hasValue($value, $source)
    {
        return ($source & $value) === $value;
    }

    /**
     * @param string $name
     * @param integer $source
     * @return boolean
     */
    public static function hasName($name, $source)
    {
        $value = static::getValue($name);

        return static::hasValue($value, $source);
    }
}
