<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Magento\Config\Model\Config\Source\Yesno;

class Screens extends Yesno
{
    /**
     * @return array[]
     */
    public function toOptionArray()
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

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'PDP' => __('Product details page'),
            'PLP' => __('Product listing page'),
            'CART' => __('Cart page'),
            'CART_FLYOUT' => __('Cart flyout'),
        ];
    }
}
