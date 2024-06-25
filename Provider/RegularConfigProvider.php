<?php

namespace Twint\Core\Provider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class RegularConfigProvider implements ConfigProviderInterface
{

    public function __construct(
        private UrlInterface $urlBuilder
    )
    {
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'twint' => [
                    'getPairingTokenUrl' => $this->urlBuilder->getUrl('twint/regular/checkout')
                ]
            ]
        ];
    }
}
