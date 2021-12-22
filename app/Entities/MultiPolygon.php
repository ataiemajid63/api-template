<?php

namespace App\Entities;

class MultiPolygon extends Entity
{
    protected $polygons;

    /**
     * @param Polygon[] $polygon
     */
    public function __construct($polygons = [])
    {
        parent::__construct();

        $this->polygons = $polygons;
    }

    /**
     * @return Polygon[]
     */
    public function getPolygons()
    {
        return $this->polygons;
    }

    /**
     * @param Polygon[]
     */
    public function setPolygons($polygons)
    {
        $this->polygons = $polygons;
    }

    /**
     * @param string $text MULTIPOLYGON(((lat long,lat long),(lat long,lat long)),((lat long,lat long),(lat long,lat long)))
     *
     * @return MultiPolygon
     */
    public static function parse($input)
    {
        $multiPolygon = new MultiPolygon();

        if(is_string($input)) {
            $geometry = str_replace('multipolygon', '', strtolower($input));
            $geometry = preg_replace('/^[(]{1}/', '', $geometry, 1);
            $geometry = preg_replace('/[)]{1}$/', '', $geometry, 1);

            $geometryPolygons = explode(')),((', $geometry);

            $polygons = [];

            foreach($geometryPolygons as $polygon) {
                $polygons[] = Polygon::parse('((' . trim($polygon, "()") . '))');
            }

            $multiPolygon->setPolygons($polygons);
        }
        else if(is_array($input) || is_object($input)) {
            $polygons = [];

            foreach($input as $item) {
                $polygons[] = Polygon::parse($item);
            }

            $multiPolygon->setPolygons($polygons);
        }

        return $multiPolygon;
    }

    /**
     * @return array
     */
    public function toArray($snake = false)
    {
        $polygons = [];

        foreach($this->getPolygons() as $polygon) {
            $polygons[] = $polygon->toArray();
        }

        return $polygons;
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
        return empty($this->getPolygons());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if($this->isEmpty()) {
            return '';
        }

        $polygons = [];

        foreach($this->getPolygons() as $polygon) {
            $lineStrings = [];

            foreach($polygon->getLineStrings() as $lineString) {
                $points = [];

                foreach($lineString->getPoints() as $point) {
                    $points[] = "{$point->getLatitude()} {$point->getLongitude()}";
                }

                $lineStrings[] = '(' . implode(',', $points) . ')';
            }

            $polygons[] = '(' . implode(',', $lineStrings) . ')';
        }

        return 'MULTIPOLYGON(' . implode(',', $polygons) . ')';
    }
}
