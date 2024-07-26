<?php

declare(strict_types=1);

namespace Twint\Magento\Observer;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Twint\Magento\Constant\TwintConstant;

class ClearCacheAfterConfigChanged implements ObserverInterface
{
    public function __construct(
        private readonly Manager $manager,
    )
    {
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('configData') ?? [];

        if (str_contains($data['section'], TwintConstant::SECTION)) {
            $this->clearCache();
        }
    }

    private function clearCache(): void
    {
        $types = [
            'config',
            'block_html',
            'layout',
            'full_page'
        ];

        $this->manager->clean($types);
    }
}
