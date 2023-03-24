<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class GeoPoint implements \JsonSerializable
{
    public function jsonSerialize(): object
    {
        return (object) [
            'type' => 'geo_point',
        ];
    }
}
