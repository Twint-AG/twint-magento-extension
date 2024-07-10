<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Payment\Block\Form\Container;
use Twint\Magento\Model\Method\TwintExpressMethod;

class PaymentFormPlugin
{
    /**
     * Remove out TWINT express method
     *
     * @param Container $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetMethods(Container $subject, callable $proceed): array
    {
        $methods = $proceed();
        foreach ($methods as $key => $method) {
            if ($method instanceof TwintExpressMethod) {
                unset($methods[$key]);
                break;
            }
        }

        $subject->setData('methods', $methods);

        return $methods;
    }
}
