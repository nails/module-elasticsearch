<?php

/**
 * Elasticsearch Search Helper: Hit
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Factory
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Factory\Search\Results;

/**
 * Class Hit
 *
 * @package Nails\Elasticsearch\Factory\Search\Result
 */
class Hit
{
    /** @var string */
    public $index;

    /** @var string */
    public $type;

    /** @var mixed */
    public $id;

    /** @var float */
    public $score;

    /** @var object */
    public $source;

    // --------------------------------------------------------------------------

    /**
     * Hit constructor.
     *
     * @param array $aHit
     */
    public function __construct(array $aHit)
    {
        $this->index  = getFromArray('_index', $aHit);
        $this->type   = getFromArray('_type', $aHit);
        $this->id     = getFromArray('_id', $aHit);
        $this->score  = getFromArray('_score', $aHit);
        $this->source = json_decode(json_encode(getFromArray('_source', $aHit)));
    }
}
