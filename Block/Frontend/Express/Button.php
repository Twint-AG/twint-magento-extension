<?php

namespace Twint\Magento\Block\Frontend\Express;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Helper\ConfigHelper;

class Button extends Template implements ShortcutInterface
{
    const SCREEN = TwintConstant::SCREEN_PDP;

    public function __construct(
        protected ConfigHelper          $configHelper,
        protected StoreManagerInterface $storeManager,
        Template\Context                $context,
        array                           $data = [])
    {
        parent::__construct($context, $data);
    }

    const ALIAS_ELEMENT_INDEX = 'alias';

    /**
     * @throws NoSuchEntityException
     */
    private function shouldRender(): bool
    {
        $config = $this->configHelper->getConfigs();
        $validated = $config->getCredentials()->getValidated();
        $screen = $config->getExpressConfig()->display(self::SCREEN);
        $currency = $this->isAllowedCurrency();

        return $validated && $screen && $currency;
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function _toHtml(): string
    {
        file_put_contents(__DIR__.'/a.txt', get_class($this), FILE_APPEND);
        if (!$this->shouldRender()) {
            return '';
        }

        return parent::_toHtml();
    }

    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function isAllowedCurrency(): bool
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode() === TwintConstant::CURRENCY;
    }
}
