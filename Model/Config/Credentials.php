<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

class Credentials extends AbstractConfig
{
    public function getCertificate(): array
    {
        return json_decode($this->data['certificate'] ?? '', true);
    }

    public function getMerchantId(): string
    {
        return $this->data['merchant_id'] ?? '';
    }

    public function getTestMode(): bool
    {
        return (bool) $this->data['test_mode'] ?? false;
    }

    public function getValidated(): bool
    {
        return (bool) $this->data['validated'] ?? false;
    }
}
