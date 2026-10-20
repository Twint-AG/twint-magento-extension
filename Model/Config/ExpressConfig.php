<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

use Twint\Magento\Constant\TwintConstant;

class ExpressConfig extends BasePaymentConfig
{
    public function getScreens(): array
    {
        return explode(',', $this->data['screens'] ?? '');
    }

    public function onScreen(string $screen): bool
    {
        return in_array($screen, $this->getScreens(), true);
    }

    public function getSuccessfulFlow(): string
    {
        $value = $this->data['successful_flow'] ?? null;
        if ($value === TwintConstant::SUCCESSFUL_FLOW_SUCCESS_PAGE) {
            return TwintConstant::SUCCESSFUL_FLOW_SUCCESS_PAGE;
        }
        return TwintConstant::SUCCESSFUL_FLOW_POPUP;
    }
}
