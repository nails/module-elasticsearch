<?php

/**
 * Elasticsearch Console Command: Warm
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Console
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Console\Command;

use Nails\Console\Command\Base;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Interfaces\Index;
use Nails\Elasticsearch\Service\Client;
use Nails\Factory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Warm
 *
 * @package Nails\Elasticsearch\Console\Command
 */
class Warm extends Base
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('elasticsearch:warm')
            ->setDescription('Warms defined indexes in Elasticsearch')
            ->addOption('index', 'i', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Define specific index to warm')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of items to index')
            ->addOption('offset', 'o', InputOption::VALUE_REQUIRED, 'Start indexing from offset');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the command
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Elasticsearch: Warm');

        // --------------------------------------------------------------------------

        $iOffset  = ((int) $oInput->getOption('offset')) ?: null;
        $iLimit   = ((int) $oInput->getOption('limit')) ?: null;
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

        // --------------------------------------------------------------------------

        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        $oClient->warm($aIndexes, $iOffset, $iLimit, $oOutput);

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
