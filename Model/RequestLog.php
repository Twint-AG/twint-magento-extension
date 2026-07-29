<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class RequestLog extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'twint_request_log';

    protected $_eventPrefix = 'twint_request_log';

    protected $_eventObject = 'twint_request_log';

    protected $_cacheTag = self::CACHE_TAG;

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\Pairing::class);
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function getRequest(): string
    {
        return $this->getData('request');
    }

    public function getResponse(): string
    {
        return $this->getData('response');
    }

    public function getSoapRequest(): string
    {
        return $this->getData('soap_request');
    }

    public function getSoapResponse(): string
    {
        return $this->getData('soap_response');
    }

    public function getException(): string
    {
        return $this->getData('exception');
    }

    public function getMethod(): string
    {
        return $this->getData('method');
    }

    public function getActions(): string
    {
        return $this->getData('soap_action');
    }
}
