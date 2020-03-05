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

use Nails\Api;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Service\Client;
use Nails\Factory;

/**
 * Class Stats
 *
 * @package Nails\Elasticsearch\Api\Controller
 */
class Stats extends \Nails\Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = false;

    // --------------------------------------------------------------------------

    /**
     * Whether the user is authenticated.
     *
     * @param string $sHttpMethod The HTTP Method protocol being used
     * @param string $sMethod     The controller method being executed
     *
     * @return array|bool
     */
    public static function isAuthenticated($sHttpMethod = '', $sMethod = '')
    {
        return parent::isAuthenticated($sHttpMethod, $sMethod)
            && userHasPermission('admin:elasticsearch:elasticsearch:view');
    }

    // --------------------------------------------------------------------------

    /**
     * Ge tthe status of the connection
     *
     * @return Api\Factory\ApiResponse
     */
    public function getConnectionStatus(): Api\Factory\ApiResponse
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData(['isAvailable' => $oClient->isAvailable()]);
    }

    // --------------------------------------------------------------------------

    /**
     * Get stats about the Elasticsearch service
     *
     * @return Api\Factory\ApiResponse
     */
    public function getIndex(): Api\Factory\ApiResponse
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        if (!$oClient->isAvailable()) {
            throw new Api\Exception\ApiException('Elasticsearch is not available.', 500);
        }

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData($oClient->cluster()->stats());
    }
}
