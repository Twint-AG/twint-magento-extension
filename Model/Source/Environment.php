<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Twint\Magento\Block\Adminhtml\Form\EnvironmentSelect;
use Twint\Sdk\Value\Environment as Option;

class Environment extends Base
{
    public function __construct(
        private readonly EnvironmentSelect $environmentSelect,
    ) {
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $options = [
            [
                'value' => Option::PRODUCTION,
                'label' => __('Production'),
            ],
        ];

        if ($this->environmentSelect->shouldDisplayTestingMode()) {
            $options[] = [
                'value' => Option::TESTING,
                'label' => __('Test'),
            ];
        }

        return $options;
    }

    public function toArray(): array
    {
        return [
            Option::PRODUCTION => __('Production'),
            Option::TESTING => __('Test'),
        ];
    }
}
