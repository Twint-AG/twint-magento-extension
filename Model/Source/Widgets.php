<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

class Widgets extends Base
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'CPL',
                'label' => __('Catalog Products List'),
            ]
        ];
    }
}
