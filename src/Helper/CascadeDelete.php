<?php

/**
 * Elasticsearch CascadeDelete Helper
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Helper
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Helper;

use Nails\Common\Model\Base;

/**
 * Class CascadeDelete
 *
 * @package Nails\Elasticsearch\Helper
 */
class CascadeDelete
{
    /**
     * The model which should be acted upon
     *
     * @var Base
     */
    protected Base $oModel;

    /**
     * The column containing the associated ID
     *
     * @var string
     */
    protected string $sColumn;

    // --------------------------------------------------------------------------

    /**
     * @param Base   $oModel  The model which should be acted upon
     * @param string $sColumn The column containing the associated ID
     */
    public function __construct(Base $oModel, string $sColumn)
    {
        $this->oModel  = $oModel;
        $this->sColumn = $sColumn;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the model which should be acted upon
     *
     * @return Base
     */
    public function getModel(): Base
    {
        return $this->oModel;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the column containing the associated ID
     *
     * @return string
     */
    public function getColumn(): string
    {
        return $this->sColumn;
    }
}
