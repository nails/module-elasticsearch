<?php

/**
 * Returns information about the currently logged in user
 *
 * @package     Nails
 * @subpackage  module-api
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Elasticsearch\Api\Controller;

use Nails\ApiApiException\ApiException;
use Nails\Factory;

class Stats extends \Nails\Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = false;

    // --------------------------------------------------------------------------

    /**
     * Ge tthe status of the connection
     * @return array
     */
    public function getConnectionStatus()
    {
        if (!userHasPermission('admin:elasticsearch:elasticsearch:view')) {
            throw new ApiException('You are not authorised to use this endpoint.', 401);
        }

        $oClient = Factory::service('Client', 'nailsapp/module-elasticsearch');
        return Factory::factory('ApiResponse', 'nailsapp/module-api')
                        ->setData(['isAvailable' => $oClient->isAvailable()]);
    }

    // --------------------------------------------------------------------------

    /**
     * Get stats about the Elasticsearch service
     * @return array
     */
    public function getIndex()
    {
        if (!userHasPermission('admin:elasticsearch:elasticsearch:view')) {
            throw new ApiException('You are not authorised to use this endpoint.', 401);

        }

        $oClient = Factory::service('Client', 'nailsapp/module-elasticsearch');
        if (!$oClient->isAvailable()) {
            throw new ApiException('Elasticsearch is not available.', 500);
        }

        return Factory::factory('ApiResponse', 'nailsapp/module-api')
                        ->setData($oClient->cluster()->stats());
    }
}
