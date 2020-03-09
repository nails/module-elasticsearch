<?php

/**
 * Elasticsearch client
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Service
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Service;

use App\Model\Listing;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\CatNamespace;
use Elasticsearch\Namespaces\ClusterNamespace;
use Elasticsearch\Namespaces\IndicesNamespace;
use Elasticsearch\Namespaces\IngestNamespace;
use Elasticsearch\Namespaces\NodesNamespace;
use Elasticsearch\Namespaces\SnapshotNamespace;
use Elasticsearch\Namespaces\TasksNamespace;
use Elasticsearch\Transport;
use GuzzleHttp\Exception\RequestException;
use Nails\Common\Factory\HttpRequest\Delete;
use Nails\Common\Factory\HttpResponse;
use Nails\Common\Service\HttpCodes;
use Nails\Components;
use Nails\Elasticsearch\Constants;
use Nails\Common\Exception\FactoryException;
use Nails\Config;
use Nails\Elasticsearch\Exception\ClientException;
use Nails\Elasticsearch\Factory\Search;
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Traits\Log;
use Nails\Elasticsearch\Traits\Model\SyncWithElasticsearch;
use Nails\Factory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Client
 *
 * @package Nails\Elasticsearch\Service
 */
class Client
{
    use Log;

    // --------------------------------------------------------------------------

    /**
     * The Elasticsearch client
     *
     * @var \Elasticsearch\Client
     */
    private $oElasticsearchClient;

    // --------------------------------------------------------------------------

    /**
     * Client constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        $this->oElasticsearchClient = Factory::service(
            'ElasticsearchClient',
            Constants::MODULE_SLUG
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the official Elasticsearch client
     *
     * @return \Elasticsearch\Client
     */
    public function getClient(): \Elasticsearch\Client
    {
        return $this->oElasticsearchClient;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether Elasticsearch is available
     *
     * @param integer $iTimeout The length of time to wait before considering the connection dead
     *
     * @return bool
     */
    public function isAvailable($iTimeout = null): bool
    {
        if (empty($iTimeout)) {
            $iTimeout = Config::get('ELATICSEARCH_TIMEOUT', 2);
        }

        $bResult = true;
        ob_start();

        try {

            $oResult = $this
                ->getClient()
                ->exists([
                    'index'  => 'test',
                    'type'   => 'test',
                    'id'     => 'test',
                    'client' => [
                        'timeout'         => $iTimeout,
                        'connect_timeout' => $iTimeout,
                    ],
                ]);

        } catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e) {
            $bResult = false;
        } catch (\Exception $e) {
            $bResult = true;
        }

        ob_end_clean();

        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Destroys all data in Elasticsearch
     *
     * @param OutputInterface|null $oOutput An output interface to log to
     *
     * @return $this
     */
    public function destroy(OutputInterface $oOutput = null): self
    {
        if (!$this->isAvailable()) {
            $this->logln($oOutput, 'Elasticsearch is not available');
            throw new ClientException(
                'Elasticsearch is not available'
            );
        }

        $this->logln($oOutput, 'Issuing destroy command...');

        try {

            $this
                ->getClient()
                ->indices()
                ->delete([
                    'index' => '*',
                ]);

            $this->logln($oOutput, 'Command acknowledged by cluster');

        } catch (ElasticsearchException $e) {
            $this->logEsError(
                $oOutput,
                'Failed to delete index',
                json_decode($e->getMessage())
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets all defined indexes in Elasticsearch
     *
     * @param OutputInterface|null $oOutput An output interface to log to
     *
     * @return $this
     */
    public function reset(OutputInterface $oOutput = null): self
    {
        if (!$this->isAvailable()) {
            $this->logln($oOutput, 'Elasticsearch is not available');
            throw new ClientException(
                'Elasticsearch is not available'
            );
        }

        $aIndexes = $this->discoverIndexes();
        if (empty($aIndexes)) {
            $this->logln($oOutput, 'No indexes to reset');
            return $this;
        }

        foreach ($aIndexes as $oIndex) {

            $sIndexString = $this->formatIndexAsString($oIndex);

            try {

                $this->log($oOutput, sprintf('- Deleting %s... ', $sIndexString));

                $this
                    ->getClient()
                    ->indices()
                    ->delete([
                        'index' => $oIndex->getIndex(),
                    ]);

                $this->logln($oOutput, '<info>done</info>');

            } catch (ElasticsearchException $e) {

                //  Tolerate deletion of non-existing index - amounts to the same thing
                if (!$e instanceof Missing404Exception) {

                    $this->logEsError(
                        $oOutput,
                        'Failed to delete index',
                        json_decode((string) $e->getResponse()->getBody())
                    );

                } else {
                    $this->logln($oOutput, '<info>done</info>');
                }
            }

            try {

                $this->log($oOutput, sprintf('- Creating %s... ', $sIndexString));

                $this
                    ->getClient()
                    ->indices()
                    ->create([
                        'index' => $oIndex->getIndex(),
                        'body'  => [
                            'settings' => $oIndex->getSettings(),
                            'mappings' => $oIndex->getMappings(),
                        ],
                    ]);

                $this->logln($oOutput, '<info>done</info>');

            } catch (ElasticsearchException $e) {
                $this->logEsError(
                    $oOutput,
                    'Failed to create index',
                    json_decode($e->getMessage())
                );
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Warms all defined indexes in Elasticsearch
     *
     * @param OutputInterface|null $oOutput An output interface to log to
     *
     * @return $this
     */
    public function warm(OutputInterface $oOutput = null): self
    {
        if (!$this->isAvailable()) {
            $this->logln($oOutput, 'Elasticsearch is not available');
            throw new ClientException(
                'Elasticsearch is not available'
            );
        }

        $aIndexes = $this->discoverIndexes();
        if (empty($aIndexes)) {
            $this->logln($oOutput, 'No indexes to warm');
            return $this;
        }

        foreach ($aIndexes as $oIndex) {

            $sTitle = sprintf(
                'Warming %s',
                $this->formatIndexAsString($oIndex)
            );
            $this->logln($oOutput, $sTitle);
            $this->logln(
                $oOutput,
                sprintf(
                    '<info>%s</info>',
                    str_repeat('-', strlen(preg_replace('/<.*?>/', '', $sTitle)))
                )
            );
            $this->logln($oOutput);

            $oIndex->warm($this, $oOutput);

            $this->logln($oOutput);
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Indexes an item
     *
     * @param Index $oIndex The index to index to
     * @param mixed $mId    The ID of the document being indexed
     * @param mixed $mBody  The body of the document
     *
     * @return $this
     */
    public function index(Index $oIndex, $mId, $mBody): self
    {
        $this
            ->getClient()
            ->index([
                'id'    => $mId,
                'index' => $oIndex->getIndex(),
                'body'  => $mBody,
            ]);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes an indexed item
     *
     * @param Index $oIndex The index to index to
     * @param mixed $mId    The ID of the document being indexed
     *
     * @return $this
     */
    public function delete(Index $oIndex, $mId): self
    {
        $this
            ->getClient()
            ->delete([
                'id'    => $mId,
                'index' => $oIndex->getIndex(),
            ]);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a new instance of Search
     *
     * @param array|string       $mQuery   The query parameters, or search keywords
     * @param Index[]|Index|null $mIndexes An array of indexes to search, or a singular index, defaults to all indexes
     *
     * @return object
     * @throws FactoryException
     */
    public function search($mQuery, $mIndexes = null): Search
    {
        return Factory::factory('Search', Constants::MODULE_SLUG, $this->getClient(), $mQuery, $mIndexes);
    }

    // --------------------------------------------------------------------------

    /**
     * Discovered Elasticsearch index definitions
     *
     * @return Index[]
     */
    public function discoverIndexes(): array
    {
        $aIndexes = [];
        foreach (Components::available() as $oComponent) {
            $oCollection = $oComponent
                ->findClasses('Elasticsearch\\Index')
                ->whichImplement(Index::class);

            foreach ($oCollection as $sClass) {
                $aIndexes[] = new $sClass();
            }
        }

        return $aIndexes;
    }

    // --------------------------------------------------------------------------

    /**
     * Logs an Elasticsearch error response
     *
     * @param OutputInterface $oOutput   The output interface to log to
     * @param string          $sError    The generic error, i.e what went wrong
     * @param object          $oResponse The actual response from ES
     *
     * @return $this
     */
    protected function logEsError(?OutputInterface $oOutput, string $sError, object $oResponse): self
    {
        if ($oOutput !== null) {
            $this->logln(
                $oOutput,
                sprintf(
                    '<error>%s. Reason: %s</error>',
                    $sError,
                    $oResponse->error->reason ?? 'Could not determine reason'
                )
            );
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats an index as a string for logging
     *
     * @param Index $oIndex The index to format
     *
     * @return string
     */
    protected function formatIndexAsString(Index $oIndex): string
    {
        return sprintf(
            '<comment>%s</comment> [<comment>%s</comment>]',
            $oIndex->getIndex(),
            get_class($oIndex)
        );
    }
}
