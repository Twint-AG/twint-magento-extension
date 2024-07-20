<?php

declare(strict_types=1);

namespace Twint\Magento\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Twint\Magento\Block\Frontend\Express\Screen\Cart\Button as CartButton;
use Twint\Magento\Block\Frontend\Express\Screen\Flyout\Button as MiniCartButton;

class AddExpressButtonObserver implements ObserverInterface
{
    /**
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        $type = $this->getPageType($observer->getEvent());
        $map = [
            'minicart' => MiniCartButton::class,
            'cart' => CartButton::class,
        ];

        if (isset($map[$type])) {
            /** @var ShortcutInterface $shortcut */
            $shortcut = $shortcutButtons->getLayout()->createBlock($map[$type]);

            $shortcutButtons->addShortcut($shortcut);
        }
    }

    private function getPageType($event): string
    {
        if ($event->getIsCatalogProduct()) {
            return 'product';
        }
        if ($event->getIsShoppingCart()) {
            return 'cart';
        }
        return 'minicart';
    }
}
