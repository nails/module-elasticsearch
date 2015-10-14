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
     * Routes calls to this class to the Elasticsearch client
     * @param  string $sMethod    The method to call
     * @param  array  $aArguments An array of arguments passed to the method
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        if (is_callable(array($this->oElasticsearchClient, $sMethod))) {

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
