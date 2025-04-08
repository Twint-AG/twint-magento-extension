<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Pairing;

class TwintRegularMethod extends TwintMethod
{
    public const CODE = 'twint_regular';

    protected $_scopeConfig;

    public $logger;

    protected $_code = self::CODE;

    public function isEnabled(string|int $storeId): bool
    {
        return (bool) $this->_scopeConfig->getValue(
            TwintConstant::REGULAR_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isInitializeNeeded(): bool
    {
        return true;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $amount = $this->priceCurrency->convertAndRound($order->getGrandTotal());

        /** @var Pairing $pairing */
        [$order, $pairing, $history] = $this->clientService->createOrder($payment, $amount);

        if (!$order) {
            throw new Exception('Unable to handle payment');
        }

        $transactionId = $pairing->getPairingId() . '-' . $history->getId();

        if ($payment instanceof Order\Payment) {
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(true);
        }
        $payment->setAdditionalInformation('pairing', $pairing->getPairingId());

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        return $this;
    }
}
