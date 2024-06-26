<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation;

class AuthorizeOperationPlugin
{
    public function __construct(
        private Session $session
    ) {
    }

    public function aroundAuthorize(
        AuthorizeOperation $subject,
        callable $proceed,
        OrderPaymentInterface $payment,
        bool $isOnline,
        float $amount
    ) {
        if (str_contains($payment->getMethod(), 'twint')) {
            $pairingId = $this->session->getPairingId();
            if (!empty($pairingId)) {
                $payment->setTransactionId($pairingId);
                $payment->setIsTransactionClosed(true);
            }
        }

        return $proceed($payment, $isOnline, $amount);
    }
}
