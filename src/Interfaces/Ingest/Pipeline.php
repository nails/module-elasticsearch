<?php

/**
 * Elasticsearch Pipeline Interface
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Interface
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Interfaces\Ingest;

/**
 * Interface Pipeline
 *
 * @package Nails\Elasticsearch\Interfaces\Ingest
 */
interface Pipeline
{
    /**
     * Returns the ID of the pipeline
     *
     * @return string
     */
    public static function getId(): string;

    /**
     * Returns the description of the pipeline
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Returns the pipeline's processors
     *
     * @return array
     */
    public static function getProcessors(): array;
}
