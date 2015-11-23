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
        $this->oElasticsearchClient = Factory::property(
            'ElasticsearchClient',
            'nailsapp/module-elasticsearch'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether elastic search is available
     * @return boolean
     */
    public function isAvailable()
    {
        if (empty($this->oElasticsearchClient)) {
            return false;
        }

        //  @todo: check the availability of the client
        try {

            return true;

        } catch (\Exception $e) {

            return false;
        }
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
        if (is_callable(array($this, $sMethod))) {

            return call_user_func_array(
                array(
                    $this,
                    $sMethod
                ),
                $aArguments
            );

        } elseif (is_callable(array($this->oElasticsearchClient, $sMethod))) {

            return call_user_func_array(
                array(
                    $this->oElasticsearchClient,
                    $sMethod
                ),
                $aArguments
            );

        } else {

            throw new \Nails\Elasticsearch\Exception\ClientException(
                '"' . $sMethod . '" is not a valid Elasticsearch Client method',
                1
            );

        }
    }
}
