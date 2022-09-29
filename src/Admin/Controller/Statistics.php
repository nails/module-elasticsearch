<?php

/**
 * Manage Elasticsearch
 *
 * @package     module-elasticsearch
 * @subpackage  Admin
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Elasticsearch\Admin\Controller;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Admin\Controller\Base;
use Nails\Elasticsearch\Admin\Permission;

class Statistics extends Base
{
    /**
     * Announces this controller's navGroups
     *
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission(Permission\Statistics\View::class)) {
            /** @var \Nails\Admin\Factory\Nav $oNavGroup */
            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
            $oNavGroup
                ->setLabel('Elasticsearch')
                ->setIcon('fa-search')
                ->addAction('Statistics');
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Manage elasticsearch
     *
     * @return void
     */
    public function index()
    {
        if (!userHasPermission(Permission\Statistics\View::class)) {
            unauthorised();
        }

        $this
            ->setTitles(['Elasticsearch', 'Statistics'])
            ->loadView('index');
    }
}
