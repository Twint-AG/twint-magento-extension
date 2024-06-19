<?php

namespace Twint\Core\Model\Config;

class BasePaymentConfig extends AbstractConfig
{
    public function getEnabled(): bool
    {
        return $this->data['enabled'] ?? false;
    }
}
