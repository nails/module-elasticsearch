<?php

namespace Nails\Elasticsearch\Console\Command;

use Nails\Console\Command\Base;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Service\Client;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Reset
 *
 * @package Nails\Elasticsearch\Console\Command
 */
class Reset extends Base
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('elasticsearch:reset')
            ->setDescription('Erases defined indexes in Elasticsearch [DESTRUCTIVE]');
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

        $this->banner('Elasticserch: Reset');

        // --------------------------------------------------------------------------

        if (Environment::is(Environment::ENV_PROD)) {

            $this->warning([
                'The app is in production.',
                'This is a highly destructive action, with no undo',
            ]);

            if (!$this->confirm('Continue?', false)) {
                return $this->abort(static::EXIT_CODE_SUCCESS);
            }
        }

        // --------------------------------------------------------------------------

        /** @var Client $oClient */
        $oClient = Factory::service('Client', Constants::MODULE_SLUG);
        $oClient->reset($oOutput);

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up...</comment>');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }
}
