<?php

namespace Nails\Elasticsearch\Admin\Permission\Statistics;

use Nails\Admin\Interfaces\Permission;

class View implements Permission
{
    public function label(): string
    {
        return 'Can view statistics';
    }

    public function group(): string
    {
        return 'Statistics';
    }
}
