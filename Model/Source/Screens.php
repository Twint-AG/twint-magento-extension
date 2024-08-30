<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Twint\Magento\Constant\TwintConstant;

class Screens extends Base
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => TwintConstant::SCREEN_PDP,
                'label' => __('Product detail page'),
            ],
            [
                'value' => TwintConstant::SCREEN_PLP,
                'label' => __('Product listing page'),
            ],
            [
                'value' => TwintConstant::SCREEN_CART,
                'label' => __('Cart page'),
            ],
            [
                'value' => TwintConstant::SCREEN_CART_FLYOUT,
                'label' => __('Cart flyout'),
            ]
        ];
    }
}
