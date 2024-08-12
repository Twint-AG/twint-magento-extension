<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Twint\Magento\Model\Method\TwintRegularMethod;
use Twint\Magento\Service\CartService;
use Twint\Magento\Service\PairingService;

class SubmitClonedQuotePlugin
{
    static array $pair = [];
    public function __construct(
        private readonly CartService $cartService,
        private readonly PairingService $pairingService
    )
    {
    }

    public function aroundSubmit(
        QuoteManagement $subject,
        \Closure $proceed,
        Quote $quote,
        array $orderData = [],
    ) {
        $payment = $quote->getPayment();
        if ($payment->getMethod() !== TwintRegularMethod::CODE) {
            return $proceed($quote, $orderData);
        }

        $cloned = $this->cartService->clone($quote);

        self::$pair = [$quote, $cloned];

        // Call the original method
        return $proceed($cloned, $orderData);
    }
}
