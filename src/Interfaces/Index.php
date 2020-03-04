<?php

namespace Nails\Elasticsearch\Interfaces;

use Nails\Elasticsearch\Service\Client;
use stdClass;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface Index
 *
 * @package Nails\Elasticsearch\Interfaces
 */
interface Index
{
    /**
     * Returns the name of the index
     *
     * @return string
     */
    public static function getIndex(): string;

    /**
     * Returns the index settings
     *
     * @return stdClass
     */
    public function getSettings(): stdClass;

    /**
     * Returns the index mappings
     *
     * @return stdClass
     */
    public function getMappings(): stdClass;

    /**
     * Warms the index
     *
     * @param Client          $oClient The Elasticsearch client
     * @param OutputInterface $oOutput The output interface being used
     *
     * @return $this
     */
    public function warm(Client $oClient, OutputInterface $oOutput);
}
