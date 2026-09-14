<?php

/**
 * Migration: 0
 * Started:   11/08/2022
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Elasticsearch\Database\Migration;

use Nails\Admin\Traits\Database\Migration\PermissionMap;
use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Elasticsearch\Admin\Permission;

/**
 * Class Migration0
 *
 * Repeatable because `feature/pre-new-admin` has no equivalent migration, so an app
 * arriving from that branch resumes above this number and would never run it.
 *
 * @package Nails\Elasticsearch\Database\Migration
 */
class Migration0 implements Interfaces\Database\Migration\Repeatable
{
    use Traits\Database\Migration;
    use PermissionMap;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:elasticsearch:elasticsearch:view' => Permission\Statistics\View::class,
    ];
}
