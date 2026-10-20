<?php

declare(strict_types=1);

namespace Twint\Magento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddNoCacheHeaders implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $action = $observer->getEvent()->getControllerAction();
        if (!$action) {
            return;
        }

        $class = get_class($action);
        // Apply only to this module's controllers
        if (str_starts_with($class, 'Twint\\Magento\\Controller\\')) {
            $response = $action->getResponse();
            if (method_exists($response, 'setNoCacheHeaders')) {
                $response->setNoCacheHeaders();
            }
        }
    }
}
