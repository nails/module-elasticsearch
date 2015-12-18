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

namespace Nails\Api\Elasticsearch;

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

            return array(
                'status' => 401,
                'error'  => 'You are not authorised to use this endpoint.'
            );

        } else {

            $oClient = Factory::service('Client', 'nailsapp/module-elasticsearch');
            return array(
                'data' => array(
                    'isAvailable' => $oClient->isAvailable()
                )
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get stats about the Elasticsearch service
     * @return array
     */
    public function getIndex()
    {
        if (!userHasPermission('admin:elasticsearch:elasticsearch:view')) {

            return array(
                'status' => 401,
                'error'  => 'You are not authorised to use this endpoint.'
            );

        } else {

            $oClient = Factory::service('Client', 'nailsapp/module-elasticsearch');
            if ($oClient->isAvailable()) {

                return array(
                    'data' => $oClient->cluster()->stats()
                );

            } else {

                return array(
                    'status' => 500,
                    'error'  => 'Elasticsearch is not available'
                );
            }
        }
    }
}
