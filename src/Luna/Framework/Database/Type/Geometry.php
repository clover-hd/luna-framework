<?php

namespace Luna\Framework\Database\Type;

abstract class Geometry extends Type
{
    public $longitude;
    public $latitude;

    public function __construct(float $latitude = null, float $longitude = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }
    
    /**
     * ネイティブの値からGeometry型を返す
     *
     * @param mixed $value
     * @return static
     */
    public function fromNativeValue($value)
    {
        return null;
    }
    
    /**
     * Geometry型からネイティブの値を返す
     *
     * @param static $value
     * @return mixed
     */
    public function toNativeValue($value)
    {
        return null;
    }

    /**
     * Geometry型からSQL文を返す
     *
     * @param mixed $value
     * @return string
     */
    public function toSqlValue($value)
    {
        return null;
    }
}