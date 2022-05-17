<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Boolean implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return (object) [
            'type' => 'boolean',
        ];
    }
}
