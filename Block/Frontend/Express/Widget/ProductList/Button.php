<?php

namespace Twint\Magento\Block\Frontend\Express\Widget\ProductList;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AwareInterface as ProductAwareInterface;
use Magento\Catalog\Model\Product;
use Twint\Magento\Block\Frontend\Express\Button as Base;
use Twint\Magento\Constant\TwintConstant;

class Button extends Base implements ProductAwareInterface
{
    private bool $forced = false;

    protected ?ProductInterface $product = null;
    const SCREEN = TwintConstant::WIDGET_CATALOG_PRODUCT_LIST;

    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
    }

    public function forceUseExpressTemplate(){
        $this->forced = true;
    }

    public function isForceToUseExpressTemplate(): bool
    {
        $config = $this->configHelper->getConfigs();

        return $config->getExpressConfig()->onScreen(static::SCREEN);
    }

    public function shouldRender(): bool
    {
        if(is_null($this->shouldRender)) {
            $config = $this->configHelper->getConfigs();
            $enabled = $config->getExpressConfig()->getEnabled();
            $validated = $config->getCredentials()->getValidated();
            $screen = $config->getExpressConfig()->onScreen(self::SCREEN);
            $currency = $this->isAllowedCurrency();

            $this->shouldRender = $enabled && $validated && (!$this->forced || $screen) && $currency;
        }

        if ($this->product instanceof Product) {
            return $this->shouldRender && $this->product->isSaleable();
        }

        return $this->shouldRender;
    }
}
