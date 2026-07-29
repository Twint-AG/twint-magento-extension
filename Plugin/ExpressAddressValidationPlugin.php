<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Closure;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;
use Twint\Magento\Model\Method\TwintExpressMethod;

class ExpressAddressValidationPlugin
{
    public function __construct(
        protected UrlInterface $urlBuilder,
    ) {
    }

    public function aroundValidateQuote(SubmitQuoteValidator $subject, Closure $proceed, Quote $quote): void
    {
        $payment = $quote->getPayment();
        if ($payment->getMethod() === TwintExpressMethod::CODE) {
            // Skip validation for TWINT Express methods
            return;
        }

        // Call the original method for TWINT Express
        $proceed($quote);
    }
}
