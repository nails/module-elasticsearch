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

use Nails\Common\Helper\ArrayHelper;

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
        $this->index  = ArrayHelper::get('_index', $aHit);
        $this->type   = ArrayHelper::get('_type', $aHit);
        $this->id     = ArrayHelper::get('_id', $aHit);
        $this->score  = ArrayHelper::get('_score', $aHit);
        $this->source = json_decode(json_encode(ArrayHelper::get('_source', $aHit)));
    }
}
