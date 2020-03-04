<?php

namespace Nails\Elasticsearch\Traits\Model;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Exception\ElasticsearchException;
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Service\Client;
use Nails\Elasticsearch\Traits;
use Nails\Factory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait Warm
 *
 * @package Nails\Elasticsearch\Traits\Model
 */
trait SyncWithElasticsearch
{
    /**
     * The Elasticsearch index to sync with
     *
     * @return Index
     */
    abstract function syncWithIndex(): Index;

    // --------------------------------------------------------------------------

    /**
     * Returns a control array to sue when saving the item to Elasticsearch
     *
     * @return array
     */
    protected function syncToElasticsearchData(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the model events which trigger a sync
     *
     * @return string[]
     */
    protected function syncToElasticsearchOn(): array
    {
        return [
            static::EVENT_CREATED,
            static::EVENT_UPDATED,
            static::EVENT_DELETED,
            static::EVENT_RESTORED,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Saves the object to Elasticsearch
     *
     * @param int $iId The ID of the item to save
     *
     * @throws FactoryException
     */
    public function syncToElasticsearch(int $iId)
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        $oItem   = $this->getById($iId, $this->syncToElasticsearchData());
        $oClient
            ->index(dd([
                'id'    => $oItem->id,
                'index' => $this->syncWithIndex()->getIndex(),
                'body'  => $oItem,
            ]));
    }

    // --------------------------------------------------------------------------

    /**
     * Overloads the triggerEvent method and executes a save
     *
     * @param string $sEvent The event to trigger
     * @param array  $aData  Data to pass to listeners
     *
     * @throws ModelException
     */
    protected function triggerEvent($sEvent, array $aData)
    {
        parent::triggerEvent($sEvent, $aData);

        if (in_array($sEvent, $this->syncToElasticsearchOn())) {

            $iId = reset($aData);
            if (!is_int($iId)) {
                throw new ElasticsearchException(
                    sprintf(
                        'Expected integer when syncing to Elasricsearch, recieved %s',
                        gettype($iId)
                    )
                );
            }

            $this->syncToElasticsearch($iId);
        }
    }
}
