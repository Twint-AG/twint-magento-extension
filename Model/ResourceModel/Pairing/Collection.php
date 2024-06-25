<?php


namespace Twint\Core\Model\ResourceModel\Pairing;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Twint\Core\Model\Pairing;
use Twint\Core\Model\ResourceModel\Pairing as ResourcePairing;

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
