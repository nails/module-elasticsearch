<?php

use Nails\Config;
use Nails\Elasticsearch\Service;
use Nails\Factory;

return [
    'services'   => [
        'Client'              => function (): Service\Client {
            if (class_exists('\App\Elasticsearch\Service\Client')) {
                return new \App\Elasticsearch\Service\Client();
            } else {
                return new Service\Client();
            }
        },
        'ElasticsearchClient' => function () {
            return \Elasticsearch\ClientBuilder::create()
                ->setHosts(
                    Config::get('ELASTIC_SEARCH_HOSTS', ['127.0.0.1:9200'])
                )
                ->build();
        },
    ],
];
