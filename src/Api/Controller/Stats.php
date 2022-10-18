<?php
/**
 * Elasticsearch API controller: Stats
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Api Controller
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Api\Controller;

use Nails\Api;
use Nails\Elasticsearch\Admin\Permission;
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
            && userHasPermission(Permission\Statistics\View::class);
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

        $oStats = json_decode(
            $oClient
                ->getClient()
                ->cluster()
                ->stats()
                ->getBody()
        );

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData([
                'cluster' => [
                    'name'   => $oStats->cluster_name ?: $oStats->cluster_uuid,
                    'status' => $oStats->status,
                ],
                'details' => [
                    [
                        'label' => 'Node Count',
                        'value' => $oStats->nodes->count->total,
                    ],
                    [
                        'label' => 'Index Count',
                        'value' => $oStats->indices->count,
                    ],
                    [
                        'label' => 'Index Size',
                        'value' => formatBytes($oStats->indices->store->size_in_bytes),
                    ],
                    [
                        'label' => 'Shard Count',
                        'value' => $oStats->indices->shards->total,
                    ],
                    [
                        'label' => 'Document Count',
                        'value' => $oStats->indices->docs->count,
                    ],
                    [
                        'label' => 'Segment Count',
                        'value' => $oStats->indices->segments->count,
                    ],
                ],
            ]);
    }
}
