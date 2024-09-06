<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Pairing;

class TwintExpressMethod extends TwintMethod
{
    public const CODE = 'twint_express';

    public $_scopeConfig;

    protected $_code = self::CODE;

    public function isEnabled(string|int $storeId): bool
    {
        return (bool)$this->_scopeConfig->getValue(
            TwintConstant::EXPRESS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getConfigPaymentAction(): string
    {
        return MethodInterface::ACTION_AUTHORIZE_CAPTURE;
    }

    public function capture(InfoInterface $payment, $amount): TwintExpressMethod|static
    {
        $amount = $this->priceCurrency->convertAndRound($amount);

        $pairing = $this->getPairing($payment);

        list($tOrder, $pairing, $history) = $this->clientService->startFastCheckoutOrder($payment, $amount, $pairing);

        $transactionId = $pairing->getPairingId() . '-' . $history->getId();

        if ($payment instanceof Payment) {
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(true);
        }
        $payment->setAdditionalInformation('pairing', $pairing->getPairingId());

        return $this;
    }

    private function getPairing(InfoInterface $payment): ?Pairing
    {
        $quoteId = $payment->getOrder()->getQuoteId();

        return $this->pairingRepository->getByQuoteId($quoteId);
    }
}
