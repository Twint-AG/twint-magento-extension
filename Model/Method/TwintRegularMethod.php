<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Throwable;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Pairing;

class TwintRegularMethod extends TwintMethod
{
    public const CODE = 'twint_regular';

    protected $_scopeConfig;

    public $logger;

    protected $_code = self::CODE;

    public function isAvailable(CartInterface $quote = null)
    {
        return $this->_scopeConfig->getValue(
            TwintConstant::REGULAR_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );
    }

    public function isActive($storeId = null)
    {
        return $this->_scopeConfig->getValue(TwintConstant::REGULAR_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function canAuthorize(): bool
    {
        return true;
    }

    public function canCapture(): bool
    {
        return true;
    }

    public function canRefund(): bool
    {
        return true;
    }

    public function canRefundPartialPerInvoice(): bool
    {
        return true;
    }

    public function canVoid(): bool
    {
        return true;
    }

    public function getConfigPaymentAction(): string
    {
        return MethodInterface::ACTION_AUTHORIZE;
    }

    public function authorize(InfoInterface $payment, $amount): self
    {
        $amount = $this->priceCurrency->convertAndRound($amount);
        /** @var Pairing $pairing */
        list($order, $pairing, $history) = $this->clientService->createOrder($payment, $amount);
        if (!$order) {
            throw new LocalizedException(__('Unable to handle payment'));
        }

        $this->checkoutSession->setPairingId($pairing->getPairingId() . '-' . $history->getId());

        return parent::authorize($payment, $amount);
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $amount = $this->priceCurrency->convertAndRound($amount);

        /** @var Order $order */
        $order = $payment->getOrder();

        $pairing = $this->pairingRepository->getByOrderId($order->getIncrementId());
        if (!($pairing instanceof Pairing)) {
            throw new LocalizedException(__('Cannot get Pairing record to refund'));
        }

        $reversalId = __('R-%1-%2', $order->getIncrementId(), time());

        try {
            $this->clientService->refund($pairing->getPairingId(), $reversalId, $amount, $order->getStoreId());
        } catch (Throwable $e) {
            $this->logger->debug([$order->getIncrementId(), $reversalId, $amount, $order->getStoreId()]);
            dd($e->getMessage());

            throw $e;
        }
    }
}
