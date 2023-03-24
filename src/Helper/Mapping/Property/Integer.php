<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Integer implements \JsonSerializable
{
    public function jsonSerialize(): object
    {
        return (object) [
            'type' => 'integer',
        ];
    }
}
