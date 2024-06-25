<?php
namespace Twint\Core\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Pairing extends AbstractModel implements IdentityInterface{
    const CACHE_TAG = 'twint_pairing';
    protected $_eventPrefix = 'twint_pairing';
    protected $_eventObject = 'twint_pairing';
    protected $_cacheTag = self::CACHE_TAG;

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\Pairing::class);
    }

    public function getCreatedAt(){
        return $this->getData('created_at');
    }
}
