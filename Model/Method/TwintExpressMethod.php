<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Twint\Magento\Constant\TwintConstant;

class TwintExpressMethod extends TwintMethod
{
    public const CODE = 'twint_express';

    public $_scopeConfig;

    protected $_code = self::CODE;

    public function isAvailable(CartInterface $quote = null)
    {
        return $this->_scopeConfig->getValue(
            TwintConstant::EXPRESS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );
    }

    public function isActive($storeId = null)
    {
        return $this->_scopeConfig->getValue(TwintConstant::EXPRESS_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
