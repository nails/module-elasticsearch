<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Nested implements \JsonSerializable
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
            'type'       => 'nested',
            'properties' => $this->aProperties,
        ];
    }
}
