<?php

/**
 * Elasticsearch client
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Library
 * @author      Nails Dev Team
 * @link
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
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Traits\Log;
use Nails\Elasticsearch\Traits\Model\SyncWithElasticsearch;
use Nails\Factory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Client
 *
 * @package Nails\Elasticsearch\Service
 *
 * @property Transport $transport;
 * @method bulk(array $params = [])
 * @method clearScroll(array $params = [])
 * @method count(array $params = [])
 * @method create(array $params = [])
 * @method delete(array $params = [])
 * @method deleteByQuery(array $params = [])
 * @method deleteByQueryRethrottle(array $params = [])
 * @method deleteScript(array $params = [])
 * @method exists(array $params = []): bool
 * @method existsSource(array $params = []): bool
 * @method explain(array $params = [])
 * @method fieldCaps(array $params = [])
 * @method get(array $params = [])
 * @method getScript(array $params = [])
 * @method getScriptContext(array $params = [])
 * @method getScriptLanguages(array $params = [])
 * @method getSource(array $params = [])
 * @method index(array $params = [])
 * @method info(array $params = [])
 * @method mget(array $params = [])
 * @method msearch(array $params = [])
 * @method msearchTemplate(array $params = [])
 * @method mtermvectors(array $params = [])
 * @method ping(array $params = []): bool
 * @method putScript(array $params = [])
 * @method rankEval(array $params = [])
 * @method reindex(array $params = [])
 * @method reindexRethrottle(array $params = [])
 * @method renderSearchTemplate(array $params = [])
 * @method scriptsPainlessExecute(array $params = [])
 * @method scroll(array $params = [])
 * @method search(array $params = [])
 * @method searchShards(array $params = [])
 * @method searchTemplate(array $params = [])
 * @method termvectors(array $params = [])
 * @method update(array $params = [])
 * @method updateByQuery(array $params = [])
 * @method updateByQueryRethrottle(array $params = [])
 * @method cat(): CatNamespace
 * @method cluster(): ClusterNamespace
 * @method indices(): IndicesNamespace
 * @method ingest(): IngestNamespace
 * @method nodes(): NodesNamespace
 * @method snapshot(): SnapshotNamespace
 * @method tasks(): TasksNamespace
 * @method extractArgument(array &$params, string $arg)
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

        if (empty($this->oElasticsearchClient)) {
            return false;
        }

        $bResult = true;
        ob_start();

        try {

            $oResult = $this->oElasticsearchClient->exists([
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
                ->oElasticsearchClient
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

            $sIndexString = sprintf(
                '<comment>%s</comment> [<comment>%s</comment>]',
                $oIndex->getIndex(),
                get_class($oIndex)
            );

            try {

                $this->log($oOutput, sprintf('- Deleting %s... ', $sIndexString));

                $this
                    ->oElasticsearchClient
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
                    ->oElasticsearchClient
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

            $sIndexString = sprintf(
                '<comment>%s</comment> [<comment>%s</comment>]',
                $oIndex->getIndex(),
                get_class($oIndex)
            );

            $sTitle = sprintf(
                'Warming %s',
                $sIndexString
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
     * Discovered Elasticsearch index definitions
     *
     * @return Index[]
     */
    protected function discoverIndexes(): array
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

    protected function logEsError(OutputInterface $oOutput, string $sError, object $oResponse): self
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
     * Routes calls to this class to the Elasticsearch client
     *
     * @param string $sMethod    The method to call
     * @param array  $aArguments An array of arguments passed to the method
     *
     * @return mixed
     */
    public function __call($sMethod, $aArguments)
    {
        return call_user_func_array(
            [
                $this->oElasticsearchClient,
                $sMethod,
            ],
            $aArguments
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Routes undefined properties to the Elasticsearch client
     *
     * @param string $sProperty The property to get
     *
     * @return mixed
     */
    public function __get($sProperty)
    {
        return $this->oElasticsearchClient->{$sProperty};
    }
}
