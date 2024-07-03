<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\TransactionStatus;

class Pairing extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'twint_pairing';

    protected $_eventPrefix = 'twint_pairing';

    protected $_eventObject = 'twint_pairing';

    protected $_cacheTag = self::CACHE_TAG;

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() === OrderStatus::SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this->getStatus() === OrderStatus::FAILURE;
    }

    public function getPairingStatus(): string
    {
        return $this->getData('pairing_status');
    }

    public function isLocked(): bool
    {
        $lock = $this->getData('lock') ?? null;
        if (!$lock) {
            return false;
        }

        return $this->getData('lock') >= $this->getData('now');
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\Pairing::class);
    }

    public function getToken(): string
    {
        return $this->getData('token');
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function isFinish(): bool
    {
        $statuses = [
            TransactionStatus::ORDER_RECEIVED,
            TransactionStatus::ORDER_CONFIRMATION_PENDING,
            TransactionStatus::ORDER_PENDING,
        ];

        return !in_array($this->getTransactionStatus(), $statuses, true);
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
