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

use Nails\Api\Factory\ApiResponse;
use Nails\ApiApiException\ApiException;
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
     * @return ApiResponse
     */
    public function getConnectionStatus(): ApiResponse
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', 'nails/module-elasticsearch');
        return Factory::factory('ApiResponse', Constants::MODULE_SLUG)
            ->setData(['isAvailable' => $oClient->isAvailable()]);
    }

    // --------------------------------------------------------------------------

    /**
     * Get stats about the Elasticsearch service
     *
     * @return ApiResponse
     */
    public function getIndex(): ApiResponse
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', 'nails/module-elasticsearch');
        if (!$oClient->isAvailable()) {
            throw new ApiException('Elasticsearch is not available.', 500);
        }

        return Factory::factory('ApiResponse', Constants::MODULE_SLUG)
            ->setData($oClient->cluster()->stats());
    }
}
