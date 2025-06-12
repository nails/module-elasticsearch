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

namespace Nails\Admin\Elasticsearch;

use Nails\Admin\Controller\Base;
use Nails\Admin\Factory\Nav;
use Nails\Admin\Helper;
use Nails\Factory;

class Elasticsearch extends Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     */
    public static function announce(): Nav|array|null
    {
        if (userHasPermission('admin:elasticsearch:elasticsearch:view')) {
            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
            $oNavGroup->setLabel('Elasticsearch');
            $oNavGroup->setIcon('fa-search');
            $oNavGroup->addAction('Statistics');
        }

        return $oNavGroup ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['view'] = 'Can manage Elasticsearch';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage elasticsearch
     *
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:elasticsearch:elasticsearch:view')) {
            unauthorised();
        }

        $this->data['page']->title = 'Elasticsearch Statistics';

        Helper::loadView('index');
    }
}
