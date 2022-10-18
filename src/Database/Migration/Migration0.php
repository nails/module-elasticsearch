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

use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Elasticsearch\Admin\Permission;

/**
 * Class Migration0
 *
 * @package Nails\Elasticsearch\Database\Migration
 */
class Migration0 implements Interfaces\Database\Migration
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:elasticsearch:elasticsearch:view' => Permission\Statistics\View::class,
    ];

    // --------------------------------------------------------------------------

    /**
     * Execute the migration
     */
    public function execute(): void
    {
        $oResult = $this->query('SELECT id, acl FROM `{{NAILS_DB_PREFIX}}user_group`');
        while ($row = $oResult->fetchObject()) {

            $acl = json_decode($row->acl) ?? [];

            foreach ($acl as &$old) {
                $old = self::MAP[$old] ?? $old;
            }

            $acl = array_filter($acl);
            $acl = array_unique($acl);
            $acl = array_values($acl);

            $this
                ->prepare('UPDATE `{{NAILS_DB_PREFIX}}user_group` SET `acl` = :acl WHERE `id` = :id')
                ->execute([
                    ':id'  => $row->id,
                    ':acl' => json_encode($acl),
                ]);
        }
    }
}
