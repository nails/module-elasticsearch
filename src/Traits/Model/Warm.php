<?php

/**
 * Elasticsearch Trait: Warm an Index based on a particular synced model
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Trait
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Traits\Model;

use Nails\Common\Helper\Tools;
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Elasticsearch\Exception\ElasticsearchException;
use Nails\Elasticsearch\Service\Client;
use Nails\Elasticsearch\Traits;
use Nails\Factory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait Warm
 *
 * @package Nails\Elasticsearch\Traits\Model
 */
trait Warm
{
    use Traits\Log;

    // --------------------------------------------------------------------------

    /**
     * The model to index
     *
     * @return Base
     */
    abstract function getModel(): Base;

    // --------------------------------------------------------------------------

    /**
     * Warms the index with items from a model
     *
     * @param Client          $oClient The Elasticsearch client
     * @param OutputInterface $oOutput The output interface being used
     * @param int|null        $iOffset Start indexing from offset (useful for testing)
     * @param int|null        $iLimit  Maximum number of items to index (useful for testing)
     *
     * @return $this
     */
    public function warm(Client $oClient, OutputInterface $oOutput, int $iOffset = null, int $iLimit = null)
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var SyncWithElasticsearch $oModel */
        $oModel = $this->getModel();

        if (!Tools::classUses($oModel, SyncWithElasticsearch::class)) {
            throw new ElasticsearchException(
                sprintf(
                    'Model %s does not use %s',
                    get_class($oModel),
                    SyncWithElasticsearch::class
                )
            );
        }

        // Select only `id` to avoid flooding memory on large tables
        $oItems = $oModel->getAllRawQuery([
            'select' => [
                $oModel->getTableAlias(true) . $oModel->getColumn('id'),
            ],
        ]);

        $iOffset       = $iOffset ?? 0;
        $iLimit        = $iLimit ?? INF;
        $iRowNumber    = 0;
        $iNumProcessed = 0;

        while (($oItem = $oItems->unbuffered_row()) && $iNumProcessed < $iLimit) {
            try {

                if ($iRowNumber < $iOffset) {
                    continue;
                }

                $this->log(
                    $oOutput,
                    sprintf(
                        '- Indexing item <info>#%s</info>... ',
                        $oItem->id,
                    )
                );

                $oModel->syncToElasticsearch($oItem->id, null);
                $this->logln($oOutput, '<info>done</info>');
                $iNumProcessed++;

            } catch (\Exception $e) {

                $oError = @json_decode($e->getMessage());
                $sError = json_last_error()
                    ? $e->getMessage()
                    : ($oError->error->reason ?? $e->getMessage());

                $this->logln($oOutput, '<error>Failed</error>');
                $this->logln($oOutput, '<error>' . $sError . '</error>');

                trigger_error(
                    sprintf(
                        'Failed to sync item with Elasticsearch [Index: %s] [Model: %s] [ID: %s] [Error: %s]',
                        get_called_class(),
                        get_class($oModel),
                        $oItem->id,
                        $sError
                    ),
                    E_USER_WARNING
                );

            } finally {
                $oDb->flushCache();
                $iRowNumber++;
            }
        }

        return $this;
    }
}
