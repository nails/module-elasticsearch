<?php

/**
 * Elasticsearch Trait: Write to the log, if available
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Trait
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Traits;

use Nails\Elasticsearch\Service\Client;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait Log
 *
 * @package Nails\Elasticsearch\Traits
 */
trait Log
{
    /**
     * Writes to the output interface, if available
     *
     * @param OutputInterface|null $oOutput   An output interface to log to
     * @param string|string[]      ...$aLines Messages to log
     *
     * @return $this
     */
    protected function log(OutputInterface $oOutput = null, ...$aLines): self
    {
        if ($oOutput !== null) {
            foreach ($aLines as $sLine) {
                $oOutput->write($sLine);
            }
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Writes to the output interface, if available
     *
     * @param OutputInterface|null $oOutput   An output interface to log to
     * @param string|string[]             ...$aLines Messages to log
     *
     * @return $this
     */
    protected function logln(OutputInterface $oOutput = null, ...$aLines): self
    {
        if ($oOutput !== null) {
            foreach ($aLines as $sLine) {
                $oOutput->writeln($sLine);
            }
        }
        return $this;
    }
}
