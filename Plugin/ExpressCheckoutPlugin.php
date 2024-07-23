<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Framework\UrlInterface;

class ExpressCheckoutPlugin
{
    public function __construct(
        protected UrlInterface $urlBuilder,
    )
    {
    }

    public function afterGetConfig(Sidebar $subject, array $result): array
    {
        return array_merge_recursive($result, $this->getConfig());
    }

    private function getConfig(): array{
        return [
            'expressCheckoutUrl' => $this->urlBuilder->getUrl('twint/express/checkout')
        ];
    }
}
