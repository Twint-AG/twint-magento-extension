<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class PairingHistory extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'twint_pairing_history';

    protected $_eventPrefix = 'twint_pairing_history';

    protected $_eventObject = 'twint_pairing_history';

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
