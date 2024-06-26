<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Twint\Sdk\Value\OrderStatus;

class Refund extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'twint_refund';

    protected $_eventPrefix = 'twint_refund';

    protected $_eventObject = 'twint_refund';

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

    public function getPairingId(): string
    {
        return $this->getData('pairing_id');
    }

    public function getReversalId(): string
    {
        return $this->getData('reversal_id');
    }

    public function getAmount(): float
    {
        return $this->getData('amount');
    }

    public function getCurrency(): string
    {
        return $this->getData('currency');
    }

    public function getReason(): string
    {
        return $this->getData('reason');
    }

    public function getStatus(): string
    {
        return $this->getData('status');
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() === OrderStatus::SUCCESS;
    }
}
