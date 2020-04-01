<?php

/**
 * Elasticsearch Console Command: Make Index
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Console
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Console\Command\Make;

use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Console\Exception\Path\DoesNotExistException;
use Nails\Console\Exception\Path\IsNotWritableException;
use Nails\Elasticsearch\Constants;
use Nails\Elasticsearch\Service\Client;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Index
 *
 * @package Nails\Elasticsearch\Console\Command
 */
class Index extends Base
{
    /**
     * The prefix given to class index class names
     *
     * @var string
     */
    const CLASS_PREFIX = '\\App\\Elasticsearch\\Index\\';

    /**
     * The permission to write files with
     *
     * @var int
     */
    const FILE_PERMISSION = 0755;

    // --------------------------------------------------------------------------

    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('make:elasticsearch:index')
            ->setDescription('Creates a new Elasticsearch Index definition')
            ->addArgument(
                'indexName',
                InputArgument::OPTIONAL,
                'Define the name of the index to create'
            );
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

        // --------------------------------------------------------------------------

        $aIndexes = $this->getIndexes();
        $this
            ->prepareIndexes($aIndexes)
            ->ensureIndexesNotExist($aIndexes)
            ->createIndexes($aIndexes);

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

    // --------------------------------------------------------------------------

    /**
     * Extracts the index class names from the user's input
     *
     * @return array
     * @throws ConsoleException
     */
    private function getIndexes(): array
    {
        $aIndexes = array_filter(explode(',', $this->oInput->getArgument('indexName')));

        if (empty($aIndexes)) {
            throw new ConsoleException(
                'No indexes supplied'
            );
        }

        $aIndexes = array_unique(
            array_map(
                function ($sIndex) {
                    $sIndex = str_replace('/', '\\', $sIndex);
                    $sIndex = preg_replace('/[^a-zA-Z0-9\\\]/', '', $sIndex);
                    $aIndex = explode('\\', $sIndex);
                    $aIndex = array_map('ucfirst', $aIndex);
                    return implode('\\', $aIndex);
                },
                $aIndexes
            )
        );

        sort($aIndexes);

        return $aIndexes;
    }

    // --------------------------------------------------------------------------

    /**
     * Converts the class names into a useful format
     *
     * @param string[] $aClasses The detected class names
     *
     * @return $this
     */
    private function prepareIndexes(array &$aClasses): self
    {
        foreach ($aClasses as &$sClass) {

            $aBits      = explode('\\', $sClass);
            $sClassName = array_pop($aBits);
            $sNamespace = trim(static::CLASS_PREFIX . implode('\\', $aBits), '\\');

            $sClass = [
                'NAMESPACE'       => $sNamespace,
                'CLASS_NAME'      => $sClassName,
                'CLASS_NAME_FULL' => $sNamespace . '\\' . $sClassName,
                'INDEX'           => str_replace('\\', '-', strtolower($sClass)),
                'PATH'            => NAILS_APP_PATH . 'src/Elasticsearch/Index/' . str_replace('\\', '/', $sClass) . '.php',
            ];
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Checks the indexes don't exist already
     *
     * @param array[] $aIndexes The index configs
     *
     * @return $this
     * @throws ConsoleException
     */
    private function ensureIndexesNotExist(array $aIndexes): self
    {
        foreach ($aIndexes as $aIndex) {
            if (class_exists($aIndex['CLASS_NAME_FULL'])) {
                throw new ConsoleException(
                    sprintf(
                        'Index already exists at %s',
                        $aIndex['CLASS_NAME_FULL']
                    )
                );
            }
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Creates the indexes
     *
     * @param array[] $aIndexes The index configs
     *
     * @return $this
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    private function createIndexes(array $aIndexes): self
    {
        $this->oOutput->writeln('');
        $this->oOutput->writeln('The following index(es) will be created:');
        foreach ($aIndexes as $aIndex) {
            $this->oOutput->writeln('');
            $this->oOutput->writeln('Class: <info>' . $aIndex['CLASS_NAME_FULL'] . '</info>');
            $this->oOutput->writeln('Index: <info>' . $aIndex['INDEX'] . '</info>');
        }
        $this->oOutput->writeln('');

        if ($this->confirm('Continue?', true)) {

            $sTpl = include __DIR__ . '/../../../../resources/console/index.php';

            foreach ($aIndexes as $aIndex) {
                $this->oOutput->write('Creating index <info>' . $aIndex['CLASS_NAME_FULL'] . '</info>... ');

                $sIndex = str_replace('{{NAMESPACE}}', $aIndex['NAMESPACE'], $sTpl);
                $sIndex = str_replace('{{CLASS_NAME}}', $aIndex['CLASS_NAME'], $sIndex);
                $sIndex = str_replace('{{CLASS_NAME_FULL}}', $aIndex['CLASS_NAME_FULL'], $sIndex);
                $sIndex = str_replace('{{INDEX}}', $aIndex['INDEX'], $sIndex);

                $sDir = dirname($aIndex['PATH']);
                if (!is_dir($sDir)) {
                    if (!@mkdir($sDir, self::FILE_PERMISSION, true)) {
                        throw new DoesNotExistException(
                            'Path "' . $sDir . '" does not exist and could not be created'
                        );
                    }
                }

                $hHandle = fopen($aIndex['PATH'], 'w');
                if (!$hHandle) {
                    throw new DoesNotExistException('Failed to open ' . $sPath . ' for writing');
                }

                if (fwrite($hHandle, $sIndex) === false) {
                    throw new IsNotWritableException('Failed to write to ' . $sPath);
                }

                fclose($hHandle);

                $this->oOutput->writeln('<info>done</info>');
            }
        }

        return $this;
    }
}
