<?php

namespace Nails\Elasticsearch\Traits\Model;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
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
     *
     * @return $this
     */
    public function warm(Client $oClient, OutputInterface $oOutput)
    {
        /** @var Database $oDb */
        $oDb    = Factory::service('Database');
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

        $oItems = $oModel->getAllRawQuery();

        while ($oItem = $oItems->unbuffered_row()) {

            $this->logln(
                $oOutput,
                sprintf(
                    '- Indexing item <info>#%s – %s</info>',
                    $oItem->id,
                    $oItem->label ?? 'No label'
                )
            );

            $oModel->syncToElasticsearch($oItem->id);
            $oDb->flushCache();
        }

        return $this;
    }
}
