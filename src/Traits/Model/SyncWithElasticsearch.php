<?php

/**
 * Elasticsearch Trait: Sync a model with an Elasticsearch index
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Trait
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Traits\Model;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
use Nails\Common\Resource;
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
     * Returns a control array to use when saving the item to Elasticsearch
     *
     * @return array
     */
    protected function syncToElasticsearchData(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * Provides a hook to alter the data before it is indexed
     *
     * @param Resource $oItem The item being indexed
     *
     * @return void
     */
    protected function beforeIndex(Resource $oItem): void
    {

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
     * @param int         $iId    The ID of the item to save
     * @param string|null $sEvent The event which triggered the sync, if any
     *
     * @throws FactoryException
     */
    public function syncToElasticsearch(int $iId, ?string $sEvent)
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);

        if ($sEvent === static::EVENT_DELETED) {
            $oClient
                ->delete(
                    $this->syncWithIndex(),
                    $iId
                );
        } else {
            $oItem = $this->getById($iId, $this->syncToElasticsearchData());
            $this->beforeIndex($oItem);
            $oClient
                ->index(
                    $this->syncWithIndex(),
                    $oItem->id,
                    $oItem
                );
        }
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
    protected function triggerEvent($sEvent, array $aData): Base
    {
        parent::triggerEvent($sEvent, $aData);

        if (in_array($sEvent, $this->syncToElasticsearchOn())) {

            $iId = reset($aData);
            if (!is_int($iId)) {
                throw new ElasticsearchException(
                    sprintf(
                        'Expected integer when syncing to Elasticsearch, recieved %s',
                        gettype($iId)
                    )
                );
            }

            $this->syncToElasticsearch($iId, $sEvent);
        }

        return $this;
    }
}
