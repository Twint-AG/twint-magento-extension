<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

class Screens extends Base
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'PDP',
                'label' => __('Product details page'),
            ],
            [
                'value' => 'PLP',
                'label' => __('Product listing page'),
            ],
            [
                'value' => 'CART',
                'label' => __('Cart page'),
            ],
            [
                'value' => 'CART_FLYOUT',
                'label' => __('Cart flyout'),
            ],
        ];
    }
}
