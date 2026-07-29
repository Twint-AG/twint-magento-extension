<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Payment\Block\Form\Container;
use Twint\Magento\Model\Method\TwintMethod;

class PaymentFormPlugin
{
    /**
     * Remove out TWINT methods
     */
    public function aroundGetMethods(Container $subject, callable $proceed): array
    {
        $methods = $proceed();
        foreach ($methods as $key => $method) {
            if ($method instanceof TwintMethod) {
                unset($methods[$key]);
                break;
            }
        }

        $subject->setData('methods', $methods);

        return $methods;
    }
}
