<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Quote\Api\Data\CartInterface;
use Twint\Magento\Constant\TwintConstant;

class TwintExpressMethod extends TwintMethod
{
    public $_scopeConfig;
    public const CODE = 'twint_express';

    protected $_code = self::CODE;

    public function isAvailable(CartInterface $quote = null)
    {
        return $this->_scopeConfig->getValue(TwintConstant::EXPRESS_ENABLED, $quote->getStoreId());
    }

    public function isActive($storeId = null)
    {
        return $this->_scopeConfig->getValue(TwintConstant::EXPRESS_ENABLED, $storeId);
    }
}
