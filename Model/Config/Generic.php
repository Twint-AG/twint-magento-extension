<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

class Generic extends AbstractConfig
{
    private Credentials $credentials;
    private BasePaymentConfig $regular;
    private ExpressConfig $express;

    public function __construct(
        protected array $data
    )
    {
        parent::__construct($data);

        $this->credentials = new Credentials($this->data['credentials'] ?? []);
        $this->regular = new BasePaymentConfig($this->data['regular'] ?? []);
        $this->express = new ExpressConfig($this->data['express'] ?? []);
    }

    public function getCredentials(): Credentials
    {
        return $this->credentials;
    }

    public function getRegularConfig(): BasePaymentConfig
    {
        return $this->regular;
    }

    public function getExpressConfig(): ExpressConfig
    {
        return $this->express;
    }
}
