<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

abstract class TwintMethod extends AbstractMethod
{
    public const CODE = 'twint_payment';

    protected $_code = self::CODE;

    public function isAvailable(CartInterface $quote = null)
    {
        return true;
    }

    public function isActive($storeId = null)
    {
        return true;
    }
}
