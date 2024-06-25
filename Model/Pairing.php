<?php

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;

class Pairing extends AbstractModel implements IdentityInterface
{
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

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function isFinish()
    {
        return $this->getStatus() === PairingStatus::NO_PAIRING;
    }

    public function getOrderId(): string
    {
        return $this->getData('order_id');
    }

    public function getStatus(): string
    {
        return $this->getData('status');
    }

    public function getPairingId(): string
    {
        return $this->getData('pairing_id');
    }

    public function getTransactionStatus(): string
    {
        return $this->getData('transaction_status');
    }

    public function getAmount(): float
    {
        return $this->getData('amount');
    }
}
