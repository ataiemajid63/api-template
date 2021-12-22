<?php

namespace App\Entities;

class MultiPoint extends Entity
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
     * @param string $input MULTIPOINT(lat long,lat long)
     *
     * @return MultiPoint
     */
    public static function parse($input)
    {
        $multiPoint = new MultiPoint();

        if(is_string($input)) {
            $geometry = str_replace('multipoint', '', strtolower($input));
            $geometry = preg_replace('/^[(]{1}/', '', $geometry, 1);
            $geometry = preg_replace('/[)]{1}$/', '', $geometry, 1);

            $geometryPoints = explode(',', $geometry);

            $points = [];

            foreach($geometryPoints as $point) {
                $points[] = Point::parse(trim($point));
            }

            $multiPoint->setPoints($points);
        }
        else if(is_array($input) || is_object($input)) {
            $points = [];

            foreach($input as $item) {
                $points[] = Point::parse($item);
            }

            $multiPoint->setPoints($points);
        }

        return $multiPoint;
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

        return 'MULTIPOINT(' . implode(',', $points) . ')';
    }
}
