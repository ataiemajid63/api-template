<?php

namespace App\Entities;

class Point extends Entity
{
    protected $latitude;
    protected $longitude;

    /**
     * Point constructor.
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct($latitude = null, $longitude = null)
    {
        parent::__construct();

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float|null $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param float|null $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @param string $input POINT(lat long)
     *
     * @return Point
     */
    public static function parse($input)
    {
        $point = new Point();

        if(is_string($input)) {
            $location = str_replace('point', '', strtolower($input));
            $location = ltrim($location, '(');
            $location = rtrim($location, ')');

            list($latitude, $longitude) = explode(' ', $location);

            $latitude = $latitude ? floatval($latitude) : null;
            $longitude = $longitude ? floatval($longitude) : null;

            $point->setLatitude($latitude);
            $point->setLongitude($longitude);
        }
        else if(is_array($input)) {
            $point->setLatitude($input['latitude']);
            $point->setLongitude($input['longitude']);
        }
        else if(is_object($input)) {
            $point->setLatitude($input->latitude);
            $point->setLongitude($input->longitude);
        }

        return $point;
    }

    /**
     * @return array
     */
    public function toArray($snake = false)
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
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
        return is_null($this->latitude) || is_null($this->longitude);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if($this->isEmpty()) {
            return '';
        }

        return "POINT({$this->latitude} {$this->longitude})";
    }
}
