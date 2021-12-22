<?php

namespace App\UseCases;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;

class UtilityBox
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function IllegiblePhoneNumber($value)
    {
        return substr_replace(str_replace([' ', '-'], '', $value), 'xxx', 4, 3);
    }

    /**
     * @param int $value
     * @param int $length
     *
     * @return string
     */
    public static function int2bit($value, $length)
    {
        return str_pad(decbin($value), $length, '0', STR_PAD_LEFT);
    }

    /**
     * @param Builder $query
     *
     * @return string
     */
    public static function getBoundSQL(Builder $query)
    {
        $fullQuery = $query->toSql();
        $replaces = $query->getBindings();
        foreach ($replaces as $replace) {
            $fullQuery = Str::replaceFirst('?', $replace, $fullQuery);
        }

        return $fullQuery;
    }
}
