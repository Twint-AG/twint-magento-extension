<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\TransactionStatus;

class Pairing extends AbstractModel implements IdentityInterface
{
    public const EXPRESS_STATUS_PAID = 'PAID';

    public const EXPRESS_STATUS_CANCELLED = 'CANCELLED';

    public const EXPRESS_STATUS_MERCHANT_CANCELLED = 'MERCHANT_CANCELLED';

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
        if ($this->isExpress()) {
            return $this->getStatus() === self::EXPRESS_STATUS_PAID;
        }

        return $this->getStatus() === OrderStatus::SUCCESS;
    }

    public function isFailure(): bool
    {
        if ($this->isExpress()) {
            return $this->getPairingStatus() === PairingStatus::NO_PAIRING;
        }

        return $this->getStatus() === OrderStatus::FAILURE;
    }

    public function getPairingStatus(): string
    {
        return $this->getData('pairing_status');
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

    public function isFinished(): bool
    {
        if ($this->isExpress()) {
            return $this->isExpressFinish();
        }

        return $this->isRegularFinish();
    }

    public function isExpressFinish(): bool
    {
        return in_array($this->getStatus(), [self::EXPRESS_STATUS_PAID, self::EXPRESS_STATUS_CANCELLED], true);
    }

    public function isRegularFinish(): bool
    {
        $statuses = [
            TransactionStatus::ORDER_RECEIVED,
            TransactionStatus::ORDER_CONFIRMATION_PENDING,
            TransactionStatus::ORDER_PENDING,
        ];

        return !in_array($this->getTransactionStatus(), $statuses, true);
    }

    public function getOrderId(): ?string
    {
        return $this->getData('order_id');
    }

    public function getQuoteId(): ?int
    {
        return (int) $this->getData('quote_id');
    }

    public function getOriginalQuoteId(): ?int
    {
        return (int) $this->getData('org_quote_id');
    }

    public function getShippingId(): ?string
    {
        return $this->getData('shipping_id');
    }

    public function getShippingCarrierCode(): string
    {
        $parts = explode('|', $this->getShippingId());

        return reset($parts);
    }

    public function getShippingMethodCode(): string
    {
        $parts = explode('|', $this->getShippingId());

        return $parts[1] ?? $parts[0];
    }

    public function getCustomerData(): ?string
    {
        return $this->getData('customer');
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function getPairingId(): string
    {
        return $this->getData('pairing_id');
    }

    public function getTransactionStatus(): ?string
    {
        return $this->getData('transaction_status');
    }

    public function getAmount(): float
    {
        return (float) $this->getData('amount');
    }

    public function getCaptured(): bool
    {
        return (bool) $this->getData('captured');
    }

    public function isExpress(): bool
    {
        return (bool) $this->getData('is_express');
    }

    public function getCheckedAgo(): int
    {
        return (int) $this->getData('checked_ago');
    }

    public function getCreatedAgo(): int
    {
        return (int) $this->getData('created_ago');
    }

    public function isTimedOut(): bool
    {
        return $this->getCreatedAgo() > ($this->isExpress() ? TwintConstant::PAIRING_TIMEOUT_EXPRESS : TwintConstant::PAIRING_TIMEOUT_REGULAR);
    }

    public function getCheckedAt()
    {
        return $this->getData('checked_at');
    }

    public function getVersion(): int
    {
        return (int) $this->getData('version');
    }

    public function getIsOrdering(): bool
    {
        return (bool) $this->getData('is_ordering');
    }

    public function isMonitoring(): bool
    {
        return $this->getCheckedAt() && $this->getCheckedAgo() < TwintConstant::MONITORING_TIME_WINDOW;
    }

    public function isSameCustomerDataWith(FastCheckoutCheckIn $checkIn): bool
    {
        $json = null;
        if ($checkIn->hasCustomerData()) {
            $json = json_encode($checkIn->customerData());
        }

        return $this->getCustomerData() === $json;
    }

    public function hasDiffs(FastCheckoutCheckIn|Order $target): bool
    {
        if ($target instanceof FastCheckoutCheckIn) {
            return $this->getPairingStatus() !== ($target->pairingStatus()->__toString() ?? '')
                || $this->getShippingId() !== ($target->hasShippingMethodId() ? (string) $target->shippingMethodId() : null);
        }


        /** @var Order $target */
        return $this->getPairingStatus() !== ($target->pairingStatus()?->__toString() ?? '')
            || $this->getTransactionStatus() !== $target->transactionStatus()
                ->__toString()
            || $this->getStatus() !== $target->status()
                ->__toString();
    }

    public function toMonitorStatus(): MonitorStatus
    {
        return MonitorStatus::fromValues(
            $this->isFinished(),
            $this->isSuccessful() ? MonitorStatus::STATUS_PAID : MonitorStatus::STATUS_CANCELLED,
            [
                'order' => $this->getOrderId(),
            ]
        );
    }
}
