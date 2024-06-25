<?php


namespace Twint\Magento\Model\ResourceModel\Pairing;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\ResourceModel\Pairing as ResourcePairing;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Pairing::class,
            ResourcePairing::class
        );
    }

}
