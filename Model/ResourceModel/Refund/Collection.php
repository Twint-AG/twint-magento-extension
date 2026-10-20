<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel\Refund;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Twint\Magento\Model\Refund;
use Twint\Magento\Model\ResourceModel\Refund as Resource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Refund::class,
            Resource::class
        );
    }
}
