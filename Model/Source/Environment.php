<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Magento\Config\Model\Config\Source\Yesno;
use Twint\Sdk\Value\Environment as Option;

class Environment extends Yesno
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

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        $assocArray = [];
        foreach ($this->toOptionArray() as $item) {
            $assocArray[$item['key']] = $item['value'];
        }

        return $assocArray;
    }
}
