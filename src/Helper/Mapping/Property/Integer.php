<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Integer implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return (object) [
            'type' => 'integer',
        ];
    }
}
