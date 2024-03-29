<?php

/**
 * Elasticsearch Search Helper
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Factory
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Factory;

use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Exception\ClientException;
use Nails\Elasticsearch\Factory\Search\Results;
use Nails\Elasticsearch\Interfaces\Index;
use Elastic\Elasticsearch\Client;
use Nails\Factory;

/**
 * Class Search
 *
 * @package Nails\Elasticsearch\Factory
 */
class Search
{
    /**
     * The default number of results
     *
     * @var int
     */
    const DEFAULT_SIZE = 10;

    // --------------------------------------------------------------------------

    /** @var Client */
    protected $oClient;

    /** @var Index[] */
    protected $aIndexes = [];

    /** @var array */
    protected $aQuery;

    // --------------------------------------------------------------------------

    /**
     * Search constructor.
     *
     * @param Client             $oClient  The Elasticsearch Client
     * @param string|array       $mQuery   The search query
     * @param Index|Index[]|null $mIndexes The indexes to search, defaults to all indexes
     *
     * @throws ClientException
     */
    public function __construct(Client $oClient, $mQuery, $mIndexes = null)
    {
        $this
            ->setClient($oClient)
            ->parseQuery($mQuery)
            ->parseIndexes($mIndexes);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the Elasticsearch client to use
     *
     * @param Client $oClient
     *
     * @return $this
     */
    public function setClient(Client $oClient): self
    {
        $this->oClient = $oClient;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Parses the query
     *
     * @param string|array|null $mQuery
     *
     * @return $this
     * @throws ClientException
     */
    protected function parseQuery($mQuery): self
    {
        if (empty($mQuery)) {
            $aQuery = [
                'body' => [
                    'query' => [
                        'match_all' => (object) [],
                    ],
                ],
            ];
        } elseif (is_string($mQuery)) {
            $aQuery = [
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => $mQuery,
                        ],
                    ],
                ],
            ];
        } elseif (is_array($mQuery)) {
            $aQuery = $mQuery;
        } else {
            throw new ClientException(
                sprintf(
                    'Expected instance of string or array, got %s',
                    gettype($mQuery)
                )
            );
        }

        $this->setQuery($aQuery);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the query to use
     *
     * @param array $aQuery the query to use
     *
     * @return $this
     */
    public function setQuery(array $aQuery): self
    {
        $this->aQuery = $aQuery;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts the supplied indexes
     *
     * Index|Index[]|null $mIndexes The indexes to search, defaults to all indexes
     *
     * @return $this
     * @throws ClientException
     */
    protected function parseIndexes($mIndexes): self
    {
        if (is_array($mIndexes)) {
            $aIndexes = $mIndexes;

        } elseif ($mIndexes instanceof Index) {
            $aIndexes = [$mIndexes];

        } else {
            $aIndexes = null;
        }

        $this->setIndexes($aIndexes);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the indexes to search
     *
     * @param array|null $aIndexes
     *
     * @return $this
     * @throws ClientException
     */
    public function setIndexes(array $aIndexes = null): self
    {
        if ($aIndexes !== null) {

            if (empty($aIndexes)) {
                throw new ClientException('No indexes to search');
            }

            foreach ($aIndexes as $mIndex) {
                if (!$mIndex instanceof Index) {
                    throw new ClientException(
                        sprintf(
                            'Expected instance of %s, got %s',
                            Index::class,
                            gettype($mIndex)
                        )
                    );
                }
            }
        }

        $this->aIndexes = $aIndexes;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the query
     *
     * @param int|null $iSize The number of results to return
     * @param int      $iPage The page of results to return
     *
     * @return Results
     */
    public function execute(int $iSize = null, int $iPage = 0): Results
    {
        $iSize = $iSize ?? static::DEFAULT_SIZE;
        $iPage = $iPage < 0 ? 0 : $iPage;

        $aResults = $this
            ->oClient
            ->search(
                $this->compile($iSize, $iPage)
            )
            ->asArray();

        return Factory::factory(
            'SearchResults',
            Constants::MODULE_SLUG,
            ['pagination' => ['size' => $iSize, 'page' => $iPage]] + $aResults
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Compiles the query
     *
     * @param int $iSize The number of results to return
     * @param int $iPage The page of results to return
     *
     * @return array
     */
    protected function compile(int $iSize, int $iPage): array
    {
        return array_merge(
            [
                'index' => $this->aIndexes === null
                    ? '_all'
                    : implode(
                        ',',
                        array_map(
                            function (Index $oIndex) {
                                return $oIndex::getIndex();
                            },
                            $this->aIndexes
                        )
                    ),
                'size'  => $iSize,
                'from'  => $iSize * (--$iPage < 0 ? 0 : $iPage),
            ],
            $this->aQuery
        );
    }
}
