<?php
namespace Twint\Core\Model\Config;

class Credentials extends AbstractConfig {

    public function getCertificate(): array{
        return json_decode($this->data['certificate'] ?? '', true);
    }

    public function getMerchantId(): string {
        return $this->data['merchant_id'] ?? '';
    }

    public function getTestMode(): string {
        return (bool) $this->data['test_mode'] ?? '';
    }

    public function getValidated(): string {
        return (bool) $this->data['validated'] ?? false;
    }
}
