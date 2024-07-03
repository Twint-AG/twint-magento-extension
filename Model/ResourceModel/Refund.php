<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Pairing resource model
 */
class Refund extends AbstractDb
{
    public const TABLE_NAME = 'twint_refund';

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }
}
