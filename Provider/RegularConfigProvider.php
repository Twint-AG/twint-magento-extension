<?php

declare(strict_types=1);

namespace Twint\Magento\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class RegularConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private UrlInterface $urlBuilder
    ) {
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                'twint' => [
                    'getPairingInformationUrl' => $this->urlBuilder->getUrl('twint/regular/payment'),
                    'getPairingStatusUrl' => $this->urlBuilder->getUrl('twint/regular/status'),
                    'getCancelPaymentUrl' => $this->urlBuilder->getUrl('twint/payment/cancel'),
                ],
            ],
        ];
    }
}
