<?php

/**
 * This file is the template for the contents of indexes
 * Used by the console command when creating indexes.
 */

return <<<'EOD'
<?php

/**
 * The "{{INDEX}}" Elasticsearch index definition
 *
 * @package  App
 * @category Elasticsearch\Index
 */

namespace {{NAMESPACE}};

use Nails\Elasticsearch;
use stdClass;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class {{CLASS_NAME}}
 *
 * @package {{NAMESPACE}}
 */
class {{CLASS_NAME}} implements Elasticsearch\Interfaces\Index
{
    /**
     * Returns the name of the index
     *
     * @return string
     */
    public static function getIndex(): string
    {
        return '{{INDEX}}';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the index settings
     *
     * @return stdClass
     */
    public function getSettings(): stdClass
    {
        return (object) [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the index mappings
     *
     * @return stdClass
     */
    public function getMappings(): stdClass
    {
        return (object) [];
    }

    // --------------------------------------------------------------------------

    /**
     * Warms the index
     *
     * @param Client          $oClient The Elasticsearch client
     * @param OutputInterface $oOutput The output interface being used
     * @param int|null        $iOffset Start indexing from offset (useful for testing)
     * @param int|null        $iLimit  Maximum number of items to index (useful for testing)
     *
     * @return $this
     */
    public function warm(Elasticsearch\Service\Client $oClient, OutputInterface $oOutput, int $iOffset = null, int $iLimit = null)
    {
        //  @todo  - Define index warming behaviour
        return $this;
    }
}

EOD;
