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

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Nails\Common\Service\HttpCodes;
use Nails\Components;
use Nails\Elasticsearch\Constants;
use Nails\Common\Exception\FactoryException;
use Nails\Config;
use Nails\Elasticsearch\Exception\ClientException;
use Nails\Elasticsearch\Factory\Search;
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Interfaces\Ingest\Pipeline;
use Nails\Elasticsearch\Traits\Log;
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
     * @var \Elastic\Elasticsearch\Client
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
     * @return \Elastic\Elasticsearch\Client
     */
    public function getClient(): \Elastic\Elasticsearch\Client
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

            $this
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

        } catch (NoNodeAvailableException $e) {
            $bResult = false;
        } catch (\Exception $e) {
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
    public function destroy(OutputInterface $oOutput = null, array $aIndexes = null, array $aPipelines = null): self
    {
        if (!$this->isAvailable()) {
            $this->logln($oOutput, 'Elasticsearch is not available');
            throw new ClientException(
                'Elasticsearch is not available'
            );
        }

        $aIndexes   = $aIndexes ?? $this->discoverIndexes();
        $aPipelines = $aPipelines ?? $this->discoverIngestPipelines();

        if (empty($aIndexes) && empty($aPipelines)) {
            $this->logln($oOutput, 'Nothing to destroy');
            return $this;
        }

        foreach ($aIndexes as $oIndex) {
            try {

                $this->log($oOutput, sprintf(
                    'Destroying index %s... ',
                    $this->formatIndexAsString($oIndex)
                ));

                $this
                    ->getClient()
                    ->indices()
                    ->delete([
                        'index' => $oIndex::getIndex(),
                    ]);

            } catch (ClientResponseException $e) {
                //  Tolerate deletion of non-existing index - amounts to the same thing
                if ($e->getCode() !== HttpCodes::STATUS_NOT_FOUND) {
                    $this->logEsError(
                        $oOutput,
                        'Failed to delete index',
                        json_decode(
                            (string) $e->getResponse()->getBody()
                        )
                    );
                }

            } finally {
                $this->logln($oOutput, '<info>done</info>');
            }
        }

        foreach ($aPipelines as $oPipeline) {
            try {

                $this->log($oOutput, sprintf(
                    'Destroying ingest pipeline %s... ',
                    $this->formatIngestPipelineAsString($oPipeline)
                ));

                $this
                    ->getClient()
                    ->ingest()
                    ->deletePipeline([
                        'id' => $oPipeline::getId(),
                    ]);

            } catch (ClientResponseException $e) {
                //  Tolerate deletion of non-existing pipeline - amounts to the same thing
                if ($e->getCode() !== HttpCodes::STATUS_NOT_FOUND) {
                    $this->logEsError(
                        $oOutput,
                        'Failed to delete ingest pipeline',
                        json_decode(
                            (string) $e->getResponse()->getBody()
                        )
                    );
                }

            } finally {
                $this->logln($oOutput, '<info>done</info>');
            }
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

        $aIndexes   = $this->discoverIndexes();
        $aPipelines = $this->discoverIngestPipelines();

        if (empty($aIndexes) && empty($aPipelines)) {
            $this->logln($oOutput, 'Nothing to reset');
            return $this;
        }

        $this->destroy($oOutput, $aIndexes, $aPipelines);

        foreach ($aIndexes as $oIndex) {
            try {

                $this->log($oOutput, sprintf(
                    'Creating %s... ',
                    $this->formatIndexAsString($oIndex)
                ));

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

        foreach ($aPipelines as $oPipeline) {
            try {

                $this->log($oOutput, sprintf(
                    'Creating ingest pipeline %s... ',
                    $this->formatIngestPipelineAsString($oPipeline)
                ));

                $response = $this
                    ->getClient()
                    ->ingest()
                    ->putPipeline([
                        'id'   => $oPipeline::getId(),
                        'body' => [
                            'description' => $oPipeline::getDescription(),
                            'processors'  => $oPipeline::getProcessors(),
                        ],
                    ]);

            } finally {
                $this->logln($oOutput, '<info>done</info>');
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Warms all defined indexes in Elasticsearch
     *
     * @param Index[]|null         $aIndexes An array of indexes to warm
     * @param OutputInterface|null $oOutput  An output interface to log to
     *
     * @return $this
     */
    public function warm(array $aIndexes = null, OutputInterface $oOutput = null): self
    {
        if (!$this->isAvailable()) {
            $this->logln($oOutput, 'Elasticsearch is not available');
            throw new ClientException(
                'Elasticsearch is not available'
            );
        }

        $aIndexes = $aIndexes ?? $this->discoverIndexes();
        if (empty($aIndexes)) {
            $this->logln($oOutput, 'No indexes to warm');
            return $this;
        }

        /** @var Index $oIndex */
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
    public function index(Index $oIndex, $mId, $mBody, array $aIndexData = []): self
    {
        $this
            ->getClient()
            ->index([
                    'id'    => $mId,
                    'index' => $oIndex->getIndex(),
                    'body'  => $mBody,
                ] + $aIndexData);

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
    public function delete(Index $oIndex, $mId, array $aIndexData = []): self
    {
        $this
            ->getClient()
            ->delete([
                    'id'    => $mId,
                    'index' => $oIndex->getIndex(),
                ] + $aIndexData);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a new instance of Search
     *
     * @param array|string       $mQuery   The query parameters, or search keywords
     * @param Index[]|Index|null $mIndexes An array of indexes to search, or a singular index, defaults to all indexes
     *
     * @return Search
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
     * Discovered Elasticsearch ingest pipeline definitions
     *
     * @return Pipeline[]
     */
    public function discoverIngestPipelines(): array
    {
        $aPipelines = [];
        foreach (Components::available() as $oComponent) {
            $oCollection = $oComponent
                ->findClasses('Elasticsearch\\Ingest\\Pipeline')
                ->whichImplement(Pipeline::class);

            foreach ($oCollection as $sClass) {
                $aPipelines[] = new $sClass();
            }
        }

        return $aPipelines;
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
            $oIndex::getIndex(),
            get_class($oIndex)
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Formats an ingest pipeline as a string for logging
     *
     * @param Pipeline $oPipeline The pipeline to format
     *
     * @return string
     */
    protected function formatIngestPipelineAsString(Pipeline $oPipeline): string
    {
        return sprintf(
            '<comment>%s</comment> [<comment>%s</comment>]',
            $oPipeline::getId(),
            get_class($oPipeline)
        );
    }
}
