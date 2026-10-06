<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Twint\Magento\Constant\TwintConstant;

class SuccessfulFlow implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => TwintConstant::SUCCESSFUL_FLOW_POPUP,
                'label' => __('Popup'),
            ],
            [
                'value' => TwintConstant::SUCCESSFUL_FLOW_SUCCESS_PAGE,
                'label' => __('Success page'),
            ],
        ];
    }
}
