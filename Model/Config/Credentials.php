<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

use Twint\Sdk\Value\Environment;

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

    public function getEnvironment(): string
    {
        return $this->data['environment'] ?? Environment::TESTING;
    }

    public function getValidated(): bool
    {
        return (bool) $this->data['validated'] ?? false;
    }
}
