<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Closure;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Twint\Magento\Model\Method\TwintRegularMethod;
use Twint\Magento\Service\CartService;

class SubmitClonedQuotePlugin
{
    public static array $pair = [];
    public static bool $cloned = false;

    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function aroundSubmit(
        QuoteManagement $subject,
        Closure $proceed,
        Quote $quote,
        array $orderData = [],
    ) {
        $payment = $quote->getPayment();
        if ($payment->getMethod() === TwintRegularMethod::CODE) {
            $cloned = $this->cartService->clone($quote);

            self::$pair = [$quote, $cloned];
            self::$cloned = true;
            $this->checkoutSession->replaceQuote($cloned);
        }

        return $proceed($quote, $orderData);
    }
}
