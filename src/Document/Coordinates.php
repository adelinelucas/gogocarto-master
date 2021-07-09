<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation\Expose;

/** @MongoDB\EmbeddedDocument */
class Coordinates
{
    /**
     * @Expose
     * @MongoDB\Field(type="float")
     */
    public $latitude = 0;

    /**
     * @Expose
     * @MongoDB\Field(type="float")
     */
    public $longitude = 0;

    public function __construct($lat = null, $lng = null)
    {
        $this->setLatitude((float) $lat);
        $this->setLongitude((float) $lng);
    }

    public function toJson()
    {
        return '{"latitude":'.$this->latitude.',"longitude":'.$this->longitude.'}';
    }

    /**
     * Set latitude.
     *
     * @param float $latitude
     *
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = ($latitude * 100000) % (180 * 100000) / 100000;

        return $this;
    }

    /**
     * Get latitude.
     *
     * @return float $latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude.
     *
     * @param float $longitude
     *
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = ($longitude * 100000) % (180 * 100000) / 100000;

        return $this;
    }

    /**
     * Get longitude.
     *
     * @return float $longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}
