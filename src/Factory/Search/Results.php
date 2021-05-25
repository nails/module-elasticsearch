<?php

/**
 * Elasticsearch Search Helper: Results
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Factory
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Factory\Search;

use Nails\Common\Helper\ArrayHelper;
use Nails\Elasticsearch\Constants;
use Nails\Factory;

/**
 * Class Results
 *
 * @package Nails\Elasticsearch\Factory\Search
 */
class Results
{

    /** @var array */
    public $pagination;

    /** @var int */
    public $took;

    /** @var bool */
    public $timed_out;

    /** @var array */
    public $shards;

    /** @var array */
    public $total;

    /** @var float */
    public $max_score;

    /** @var array */
    public $hits;

    // --------------------------------------------------------------------------

    /**
     * Results constructor.
     *
     * @param array $aResults The results from Elasticsearch
     */
    public function __construct(array $aResults)
    {
        $aHits            = ArrayHelper::get('hits', $aResults, []);
        $this->pagination = ArrayHelper::get('pagination', $aResults);
        $this->took       = ArrayHelper::get('took', $aResults);
        $this->timed_out  = ArrayHelper::get('timed_out', $aResults);
        $this->shards     = ArrayHelper::get('_shards', $aResults);
        $this->total      = ArrayHelper::get('total', $aHits, []);
        $this->max_score  = ArrayHelper::get('max_score', $aHits);
        $this->hits       = array_map(
            function (array $aHit) {
                return Factory::factory('SearchResultsHit', Constants::MODULE_SLUG, $aHit);
            },
            ArrayHelper::get('hits', $aHits, [])
        );
    }
}
