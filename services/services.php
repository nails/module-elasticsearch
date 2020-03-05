<?php

use Nails\Config;
use Nails\Elasticsearch;
use Nails\Factory;

return [
    'services'  => [
        'Client'              => function (): Elasticsearch\Service\Client {
            if (class_exists('\App\Elasticsearch\Service\Client')) {
                return new \App\Elasticsearch\Service\Client();
            } else {
                return new Elasticsearch\Service\Client();
            }
        },
        'ElasticsearchClient' => function (): \Elasticsearch\Client {
            return \Elasticsearch\ClientBuilder::create()
                ->setHosts(
                    Config::get('ELASTICSEARCH_HOSTS', ['127.0.0.1:9200'])
                )
                ->build();
        },
    ],
    'factories' => [
        'Search'           => function (
            \Elasticsearch\Client $oClient,
            $mQuery,
            $mIndexes = null
        ): Elasticsearch\Factory\Search {
            if (class_exists('\App\Elasticsearch\Factory\Search')) {
                return new \App\Elasticsearch\Factory\Search($oClient, $mQuery, $mIndexes);
            } else {
                return new Elasticsearch\Factory\Search($oClient, $mQuery, $mIndexes);
            }
        },
        'SearchResults'    => function (array $aResults): Elasticsearch\Factory\Search\Results {
            if (class_exists('\App\Elasticsearch\Factory\Search\Results')) {
                return new \App\Elasticsearch\Factory\Search\Results($aResults);
            } else {
                return new Elasticsearch\Factory\Search\Results($aResults);
            }
        },
        'SearchResultsHit' => function (array $aHit): Elasticsearch\Factory\Search\Results\Hit {
            if (class_exists('\App\Elasticsearch\Factory\Search\Results\Hit')) {
                return new \App\Elasticsearch\Factory\Search\Results\Hit($aHit);
            } else {
                return new Elasticsearch\Factory\Search\Results\Hit($aHit);
            }
        },
    ],
];
