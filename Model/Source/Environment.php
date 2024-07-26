<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Twint\Sdk\Value\Environment as Option;

class Environment extends Base
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Option::PRODUCTION,
                'label' => __('Production'),
            ],
            [
                'value' => Option::TESTING,
                'label' => __('Test'),
            ]
        ];
    }
}
