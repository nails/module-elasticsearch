<?php

return array(
    'properties' => array(
        'hosts' => array()
    ),
    'services' => array(
        'Client' => function() {

            return new \Nails\Elasticsearch\Library\Client();
        },
        'ElasticsearchClient' => function() {

            $aHosts = \Nails\Factory::property('hosts', 'nailsapp/module-elasticsearch');

            return \Elasticsearch\ClientBuilder::create()
                ->setHosts($aHosts)
                ->build();
        }
    )
);
