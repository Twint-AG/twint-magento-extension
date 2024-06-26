<?php

declare(strict_types=1);

namespace Twint\Magento\Model\ResourceModel\RequestLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Twint\Magento\Model\RequestLog;
use Twint\Magento\Model\ResourceModel\RequestLog as Resource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            RequestLog::class,
            Resource::class
        );
    }
}
