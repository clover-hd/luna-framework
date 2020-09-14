<?php

namespace Luna\Framework\Database\Type;

class MYSQLGeometry extends Geometry
{
    public function fromNativeValue($value)
    {
        $result = unpack('Lpadding/corder/Lgtype/dlatitude/dlongitude', $value);
        if (is_array($result)) {
            return new static($result['latitude'], $result['longitude']);
        }
        return null;
    }

    public function toNativeValue($value)
    {
        $data = pack('LcLd2', 0, 1, 1, $value->latitude, $value->longitube);
        return \bin2hex($data);
    }

    public function toSqlValue($value)
    {
        return "GeomFromText('POINT({$value->latitude} {$value->longitude})')";
    }
}