<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Twint\Magento\Constant\TwintConstant;

class Widgets extends Base
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => TwintConstant::WIDGET_CATALOG_PRODUCT_LIST,
                'label' => __('Catalog Products List'),
            ]
        ];
    }
}
