<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Pairing resource model
 */
class RequestLog extends AbstractDb
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('twint_request_log', 'id');
    }
}
