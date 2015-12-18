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

namespace Nails\Elasticsearch\Library;

use Nails\Factory;

class Client
{
    private $oElasticsearchClient;

    // --------------------------------------------------------------------------

    /**
     * Set up library
     */
    public function __construct()
    {
        $this->oElasticsearchClient = Factory::service(
            'ElasticsearchClient',
            'nailsapp/module-elasticsearch'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether Elasticsearch is available
     * @return boolean
     */
    public function isAvailable()
    {
        if (empty($this->oElasticsearchClient)) {
            return false;
        }

        $bResult = true;
        ob_start();

        try {


            $oResult = $this->oElasticsearchClient->exists(
                array(
                    'index' => 'test',
                    'type' => 'test',
                    'id' => 'test'
                )
            );

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
     * @param  string $sMethod    The method to call
     * @param  array  $aArguments An array of arguments passed to the method
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        return call_user_func_array(
            array(
                $this->oElasticsearchClient,
                $sMethod
            ),
            $aArguments
        );
    }
}
