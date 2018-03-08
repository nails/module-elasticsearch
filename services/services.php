<?php

use Nails\Factory;

return [
    'properties' => [
        'hosts'   => ['localhost:9200'],
        'timeout' => 2,
    ],
    'services' => [
        'Client' => function () {
            if (class_exists('\App\Elasticsearch\Service\Client')) {
                return new \App\Elasticsearch\Service\Client();
            } else {
                return new \Nails\Elasticsearch\Service\Client();
            }
        },
        'ElasticsearchClient' => function () {

            $aHosts = Factory::property('hosts', 'nailsapp/module-elasticsearch');

            return \Elasticsearch\ClientBuilder::create()
                ->setHosts($aHosts)
                ->build();
        },
    ],
];
