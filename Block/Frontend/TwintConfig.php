<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Twint\Magento\Helper\ConfigHelper;

class TwintConfig extends Template
{
    public function __construct(
        Context $context,
        protected UrlInterface $urlBuilder,
        protected ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getConfig(): array
    {
        $configs = $this->configHelper->getConfigs();
        return [
            'expressCheckoutUrl' => $this->urlBuilder->getUrl('twint/express/checkout'),
            'expressStatusUrl' => $this->urlBuilder->getUrl('twint/express/status'),
            'cancelCheckoutUrl' => $this->urlBuilder->getUrl('twint/payment/cancel'),
            'shoppingCartUrl' => $this->urlBuilder->getUrl('checkout/cart'),
            'successfulFlow' => $configs->getExpressConfig()->getSuccessfulFlow(),
        ];
    }
}
