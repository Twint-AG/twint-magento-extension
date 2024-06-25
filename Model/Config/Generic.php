<?php

namespace Twint\Magento\Model\Config;

class Generic extends AbstractConfig
{
    public function getCredentials(): Credentials
    {
        return new Credentials($this->data['credentials'] ?? []);
    }

    public function getRegularConfig(): BasePaymentConfig
    {
        return new BasePaymentConfig($this->data['regular'] ?? []);
    }

    public function getExpressConfig(): BasePaymentConfig
    {
        return new BasePaymentConfig($this->data['express'] ?? []);
    }
}
