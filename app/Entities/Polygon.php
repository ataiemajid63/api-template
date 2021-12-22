<?php

namespace App\Entities;

class Polygon extends Entity
{
    protected $lineStrings;

    /**
     * @param LineString[] $lineStrings
     */
    public function __construct($lineStrings = [])
    {
        parent::__construct();

        $this->lineStrings = $lineStrings;
    }

    /**
     * @return LineString[]
     */
    public function getLineStrings()
    {
        return $this->lineStrings;
    }

    /**
     * @param LineString[]
     */
    public function setLineStrings($lineStrings)
    {
        $this->lineStrings = $lineStrings;
    }

    /**
     * @param string $input POLYGON((lat long,lat long),(lat long,lat long))
     *
     * @return Polygon
     */
    public static function parse($input)
    {
        $polygon = new Polygon();

        if(is_string($input)) {
            $geometry = str_replace('polygon', '', strtolower($input));
            $geometry = trim($geometry);
            $geometry = preg_replace('/^[(]{1}/', '', $geometry, 1);
            $geometry = preg_replace('/[)]{1}$/', '', $geometry, 1);

            $geometryLineStrings = explode('),(', $geometry);

            $lineStrings = [];

            foreach($geometryLineStrings as $lineString) {
                $lineStrings[] = LineString::parse('(' . trim($lineString, "( )") . ')');
            }

            $polygon->setLineStrings($lineStrings);
        }
        else if(is_array($input) || is_object($input)) {
            $lineStrings = [];

            foreach($input as $item) {
                $lineStrings[] = LineString::parse($item);
            }

            $polygon->setLineStrings($lineStrings);
        }

        return $polygon;
    }

    /**
     * @return array
     */
    public function toArray($snake = false)
    {
        $lineStrings = [];

        foreach($this->getLineStrings() as $lineString) {
            $lineStrings[] = $lineString->toArray();
        }

        return $lineStrings;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->getLineStrings());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if($this->isEmpty()) {
            return '';
        }

        $lineStrings = [];

        foreach($this->getlineStrings() as $lineString) {
            $points = [];

            foreach($lineString->getPoints() as $point) {
                $points[] = "{$point->getLatitude()} {$point->getLongitude()}";
            }

            $lineStrings[] = '(' . implode(',', $points) . ')';
        }

        return 'POLYGON(' . implode(',', $lineStrings) . ')';
    }
}
