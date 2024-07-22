<?php

namespace Twint\Magento\Block\Frontend\Express\Screen\Category;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AwareInterface as ProductAwareInterface;
use Magento\Catalog\Model\Product;
use Twint\Magento\Block\Frontend\Express\Button as Base;
use Twint\Magento\Constant\TwintConstant;

class Button extends Base implements ProductAwareInterface
{
    protected ProductInterface $product;
    const SCREEN = TwintConstant::SCREEN_PLP;

    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
    }

    protected function shouldRender(): bool
    {
        $should = parent::shouldRender();

        if ($this->product instanceof Product) {
            return $should && $this->product->isSaleable();
        }

        return $should;
    }
}
