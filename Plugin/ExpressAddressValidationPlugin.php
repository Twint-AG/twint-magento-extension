<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;
use Twint\Magento\Model\Method\TwintExpressMethod;

class ExpressAddressValidationPlugin
{
    public function __construct(
        protected UrlInterface $urlBuilder,
    )
    {
    }

    /**
     * @throws Exception
     * @throws LocalizedException
     */
    public function aroundValidateQuote(SubmitQuoteValidator $validator, callable $process, Quote $quote): void
    {
        $payment = $quote->getPayment();
        if ($payment->getMethod() !== TwintExpressMethod::CODE) {
            $validator->validateQuote($quote);
        }
    }
}
