<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

class ExpressConfig extends BasePaymentConfig
{
    public function getScreens(): array
    {
        return explode(',', $this->data['screens'] ?? '');
    }

    public function display(string $screen): bool
    {
        return in_array($screen, $this->getScreens());
    }
}
