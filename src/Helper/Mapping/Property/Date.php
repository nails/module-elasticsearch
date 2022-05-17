<?php

namespace Nails\Elasticsearch\Helper\Mapping\Property;

class Date implements \JsonSerializable
{
    protected string $sFormat;

    public function __construct(string $sFormat = null)
    {
        $this->sFormat = $sFormat ?? 'yyy-MM-dd HH:mm:ss';
    }

    public function jsonSerialize()
    {
        return (object) [
            'type'   => 'date',
            'format' => $this->sFormat,
        ];
    }
}
