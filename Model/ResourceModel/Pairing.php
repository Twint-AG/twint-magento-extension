<?php
namespace Twint\Core\Model\ResourceModel;

/**
 * Pairing resource model
 */
class Pairing extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('twint_pairing', 'id');
    }
}
