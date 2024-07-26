<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

class ExpressConfig extends BasePaymentConfig
{
    public function getScreens(): array
    {
        return explode(',', $this->data['screens'] ?? '');
    }

    public function onScreen(string $screen): bool
    {
        return in_array($screen, $this->getScreens());
    }

    public function getWidgets(): array
    {
        return explode(',', $this->data['widgets'] ?? '');
    }

    public function onWidget(string $widget): bool
    {
        return in_array($widget, $this->getWidgets());
    }
}
