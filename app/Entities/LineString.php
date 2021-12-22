<?php

namespace App\Entities;

class LineString extends Entity
{
    protected $points;

    /**
     * @param Point[] $point
     */
    public function __construct($points = [])
    {
        parent::__construct();

        $this->points = $points;
    }

    /**
     * @return Point[]
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param Point[]
     */
    public function setPoints($points)
    {
        $this->points = $points;
    }

    /**
     * @param string $input LINESTRING(lat long,lat long)
     *
     * @return LineString
     */
    public static function parse($input)
    {
        $lineString = new LineString();

        if(is_string($input)) {
            $geometry = str_replace('linestring', '', strtolower($input));
            $geometry = preg_replace('/^[(]{1}/', '', $geometry, 1);
            $geometry = preg_replace('/[)]{1}$/', '', $geometry, 1);

            $geometryPoints = explode(',', $geometry);

            $points = [];

            foreach($geometryPoints as $point) {
                $points[] = Point::parse(trim($point));
            }

            $lineString->setPoints($points);
        }
        else if(is_array($input) || is_object($input)) {
            $points = [];

            foreach($input as $item) {
                $points[] = Point::parse($item);
            }

            $lineString->setPoints($points);
        }

        return $lineString;
    }

    /**
     * @return array
     */
    public function toArray($snake = false)
    {
        $points = [];

        foreach($this->getPoints() as $point) {
            $points[] = $point->toArray();
        }

        return $points;
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
        return empty($this->getPoints());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if($this->isEmpty()) {
            return '';
        }

        $points = [];

        foreach($this->getPoints() as $point) {
            $points[] = "{$point->getLatitude()} {$point->getLongitude()}";
        }

        return 'LINESTRING(' . implode(',', $points) . ')';
    }
}
