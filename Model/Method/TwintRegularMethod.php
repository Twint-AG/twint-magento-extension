<?php
namespace Twint\Core\Model\Method;

use Magento\Payment\Model\InfoInterface;

class TwintRegularMethod extends TwintMethod{
    const CODE = 'twint_regular';

    protected $_code = self::CODE;

    public function capture(InfoInterface $payment, $amount)
    {
        dd($payment, $amount);
    }

    public function canCapture()
    {
        return true;
    }
}
