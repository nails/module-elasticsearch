<?php

/**
 * Elasticsearch Console Command: Update
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Console
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Console\Command;

use Nails\Common\Exception\FactoryException;
use Nails\Console\Command\Base;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Exception\ClientException;
use Nails\Elasticsearch\Service\Client;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Sync
 *
 * @package Nails\Elasticsearch\Console\Command
 */
class Sync extends Base
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('elasticsearch:sync')
            ->setDescription('Non-destructively syncs mappings and settings to defined indexes');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the command
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     * @throws FactoryException
     * @throws ClientException
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Elasticsearch: Sync');

        // --------------------------------------------------------------------------

        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        $oClient->sync($oOutput);

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up</comment>...');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }
}
