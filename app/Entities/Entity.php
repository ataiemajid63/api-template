<?php

namespace App\Entities;

use Illuminate\Support\Str;

class Entity implements \JsonSerializable
{
    public function __construct()
    {

    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $function = Str::camel('set_' . Str::snake($name));
            $this->$function($value);
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            $function = Str::camel('get_' . Str::snake($name));
            return $this->$function();
        }
    }

    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * get an Array of Properties
     *
     * @param bool $snake If true, change properties names to snake case.
     */
    public function toArray($snake = false)
    {
        $properties = get_object_vars($this);

        $items = [];

        foreach($properties as $name => $value) {
            $name = $snake ? Str::snake($name) : $name;

            if($value instanceof Entity) {
                $items[$name] = $value->toArray($snake);
            }
            else {
                $items[$name] = $value;
            }
        }

        return $items;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
