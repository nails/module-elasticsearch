<?php

/**
 * Elasticsearch client
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Library
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Elasticsearch\Service;

use Nails\Elasticsearc\Constants;
use Nails\Common\Exception\FactoryException;
use Nails\Config;
use Nails\Factory;

/**
 * Class Client
 *
 * @package Nails\Elasticsearch\Service
 */
class Client
{
    /**
     * The Elasticsearch client
     *
     * @var object \Elasticsearch\Client
     */
    private $oElasticsearchClient;

    // --------------------------------------------------------------------------

    /**
     * Client constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        $this->oElasticsearchClient = Factory::service(
            'ElasticsearchClient',
            Constants::MODULE_SLUG
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether Elasticsearch is available
     *
     * @param integer $iTimeout The length of time to wait before considering the connection dead
     *
     * @return bool
     */
    public function isAvailable($iTimeout = null): bool
    {
        if (empty($iTimeout)) {
            $iTimeout = Config::get('ELATICSEARCH_TIMEOUT', 2);
        }

        if (empty($this->oElasticsearchClient)) {
            return false;
        }

        $bResult = true;
        ob_start();

        try {

            $oResult = $this->oElasticsearchClient->exists([
                'index'  => 'test',
                'type'   => 'test',
                'id'     => 'test',
                'client' => [
                    'timeout'         => $iTimeout,
                    'connect_timeout' => $iTimeout,
                ],
            ]);

        } catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e) {
            $bResult = false;
        } catch (\Exception $e) {
            $bResult = true;
        }

        ob_end_clean();

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Routes calls to this class to the Elasticsearch client
     *
     * @param string $sMethod    The method to call
     * @param array  $aArguments An array of arguments passed to the method
     *
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        return call_user_func_array(
            [
                $this->oElasticsearchClient,
                $sMethod,
            ],
            $aArguments
        );
    }
}
