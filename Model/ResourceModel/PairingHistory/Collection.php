<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel\PairingHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Model\ResourceModel\PairingHistory as Resource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            PairingHistory::class,
            Resource::class
        );
    }
}
