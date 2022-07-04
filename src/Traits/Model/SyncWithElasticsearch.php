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
use Nails\Common\Helper\Model\Where;
use Nails\Common\Model\Base;
use Nails\Common\Resource;
use Nails\Common\Service\Database;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Exception\ElasticsearchException;
use Nails\Elasticsearch\Helper\CascadeDelete;
use Nails\Elasticsearch\Helper\CascadeIndex;
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
    abstract public function syncWithIndex(): Index;

    /**
     * Enforce the `skipCache` method
     */
    abstract public function skipCache(): Base;

    /**
     * Enforce the `getById` method
     */
    abstract public function getById($iId, array $aData = []);

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
     * Returns an array of items which should be deleted when an item is deleted
     *
     * @return CascadeDelete[]
     */
    protected function syncCascadeDelete(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of items which should be re-indexed when an item is indexed
     *
     * @return CascadeIndex[]
     */
    protected function syncCascadeIndex(): array
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
     * @param int         $iId    The ID of the item to save
     * @param string|null $sEvent The event which triggered the sync, if any
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function syncToElasticsearch(int $iId, string $sEvent = null): void
    {
        if ($sEvent === static::EVENT_DELETED) {

            $this
                ->deleteItemFromElasticsearch($iId)
                ->deleteItemFromElasticsearchCascade($iId);

        } else {

            $this
                ->indexItemToElasticsearch($iId)
                ->indexItemToElasticsearchCascade($iId);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes the item from Elasticsearch
     *
     * @param int $iId The ID of the object being deleted
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    protected function deleteItemFromElasticsearch(int $iId): self
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);

        $oClient
            ->delete(
                $this->syncWithIndex(),
                $iId
            );

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Performs any cascading deletions
     *
     * @param int $iId The ID of the object being deleted
     *
     * @return $this
     * @throws FactoryException
     * @throws \Nails\Common\Exception\ModelException
     */
    protected function deleteItemFromElasticsearchCascade(int $iId): self
    {
        foreach ($this->syncCascadeDelete() as $oCascade) {

            $oModel = $oCascade->getModel();

            $aIds = $oModel
                ->skipCache()
                ->getIds([
                    new Where($oCascade->getColumn(), $iId),
                ]);

            $oModels
                ->skipDeleteExistsCheck()
                ->deleteMany($aIds);
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the item to index to Elasticsearch
     *
     * @param int $iId The ID of the object being indexed
     *
     * @return Resource
     * @throws ModelException
     */
    protected function getItemToIndexToElasticsearch(int $iId): Resource
    {
        return $this
            ->skipCache()
            ->includeDeleted()
            ->getById($iId, $this->syncToElasticsearchData());
    }

    // --------------------------------------------------------------------------

    /**
     * Indexes as resource to Elasticsearch
     *
     * @param \Nails\Common\Resource $oItem The item to index
     *
     * @return $this
     * @throws FactoryException
     */
    protected function indexItemToElasticsearch(int $iId): self
    {
        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);

        $oItem = $this->getItemToIndexToElasticsearch($iId);

        if (!empty($oItem)) {
            $oClient
                ->index(
                    $this->syncWithIndex(),
                    $oItem->id,
                    $oItem
                );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    protected function indexItemToElasticsearchCascade(int $iId): self
    {
        foreach ($this->syncCascadeIndex() as $oCascade) {

            $oModel = $oCascade->getModel();

            $aIds = $oModel
                ->skipCache()
                ->getIds([
                    new Where($oCascade->getColumn(), $iId),
                ]);

            foreach ($aIds as $iId) {
                $oModel
                    ->indexItemToElasticsearch($iId);
            }
        }

        return $this;
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
