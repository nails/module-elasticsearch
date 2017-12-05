<?php

use Nails\Factory;

return [
    'properties' => [
        'hosts'   => ['localhost:9200'],
        'timeout' => 2,
    ],
    'services' => [
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
        },
    ],
];
