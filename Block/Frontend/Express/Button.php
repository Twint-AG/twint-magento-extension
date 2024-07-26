<?php

namespace Twint\Magento\Block\Frontend\Express;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Helper\ConfigHelper;

class Button extends Template implements ShortcutInterface
{
    protected ?bool $shouldRender = null;

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
    public function shouldRender(): bool
    {
        if (is_null($this->shouldRender)) {
            $config = $this->configHelper->getConfigs();
            $validated = $config->getCredentials()->getValidated();
            $screen = $config->getExpressConfig()->onScreen(self::SCREEN);
            $currency = $this->isAllowedCurrency();

            $this->shouldRender = $validated && $screen && $currency;
        }

        return $this->shouldRender;
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function _toHtml(): string
    {
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
