<?php
namespace Twint\Core\Model\Method;

use Magento\Payment\Model\Method\AbstractMethod;

abstract class TwintMethod extends AbstractMethod{
    const CODE = 'twint_payment';

    protected $_code = self::CODE;

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    public function isActive($storeId = null)
    {
        return true;
    }
}
