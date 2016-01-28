<?php

use Nails\Factory;

return array(
    'properties' => array(
        'hosts'   => array(),
        'timeout' => 2
    ),
    'services' => array(
        'Client' => function () {
            if (class_exists('\App\Elasticsearch\Library\Client')) {
                return new \App\Elasticsearch\Library\Client();
            } else {
                return new \Nails\Elasticsearch\Library\Client();
            }
        },
        'ElasticsearchClient' => function () {

            $aHosts = Factory::property('hosts', 'nailsapp/module-elasticsearch');

            return \Elasticsearch\ClientBuilder::create()
                ->setHosts($aHosts)
                ->build();
        }
    )
);
