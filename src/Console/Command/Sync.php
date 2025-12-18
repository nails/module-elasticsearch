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
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Service\Client;
use Nails\Factory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Non-destructively syncs mappings and settings to defined indexes')
            ->addOption('index', 'i', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Define specific index to sync')
            ->addOption('mappings', 'm', InputOption::VALUE_NONE, 'Only sync mappings')
            ->addOption('settings', 's', InputOption::VALUE_NONE, 'Only sync settings');
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

        $aIndexes = $oInput->getOption('index') ?: null;
        if (!empty($aIndexes)) {
            $aIndexes = array_map(function (string $sClass) {
                if (!class_exists($sClass) || !classImplements($sClass, Index::class)) {
                    throw new InvalidOptionException(
                        sprintf(
                            '"%s" is not a valid Index',
                            $sClass
                        )
                    );
                }

                return new $sClass();
            }, $aIndexes);
        }

        $bMappings = $oInput->getOption('mappings');
        $bSettings = $oInput->getOption('settings');

        if (!$bMappings && !$bSettings) {
            $bMappings = true;
            $bSettings = true;
        }

        // --------------------------------------------------------------------------

        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        $oClient->sync($aIndexes, $oOutput, $bSettings, $bMappings);

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
