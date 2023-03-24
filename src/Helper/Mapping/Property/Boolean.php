<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Boolean implements \JsonSerializable
{
    public function jsonSerialize(): object
    {
        return (object) [
            'type' => 'boolean',
        ];
    }
}
