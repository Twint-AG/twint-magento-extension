<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Store\Model\ScopeInterface;
use Twint\Magento\Constant\TwintConstant;

class TwintRegularMethod extends TwintMethod
{
    public const CODE = 'twint_regular';

    protected $_scopeConfig;

    public $logger;

    protected $_code = self::CODE;

    public function isEnabled(string|int $storeId): bool
    {
        return (bool) $this->_scopeConfig->getValue(
            TwintConstant::REGULAR_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
