<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Obj implements \JsonSerializable
{
    private array $aProperties;

    // --------------------------------------------------------------------------

    public function __construct(array $aProperties = [])
    {
        $this->aProperties = $aProperties;
    }

    // --------------------------------------------------------------------------

    public function jsonSerialize(): object
    {
        return (object) [
            'type'       => 'object',
            'properties' => $this->aProperties,
        ];
    }
}
