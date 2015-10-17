<?php

return array(
    'properties' => array(
        'hosts' => array()
    ),
    'services' => array(
        'Client' => function() {
            if (class_exists('\App\Elasticsearch\Library\Client')) {
                return new \App\Elasticsearch\Library\Client();
            } else {
                return new \Nails\Elasticsearch\Library\Client();
            }
        },
        'ElasticsearchClient' => function() {

            $aHosts = \Nails\Factory::property('hosts', 'nailsapp/module-elasticsearch');

            return \Elasticsearch\ClientBuilder::create()
                ->setHosts($aHosts)
                ->build();
        }
    )
);
