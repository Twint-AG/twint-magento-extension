<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend\Express;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Helper\ConfigHelper;

class Button extends Template implements ShortcutInterface
{
    public const SCREEN = TwintConstant::SCREEN_PDP;

    public const ALIAS_ELEMENT_INDEX = 'alias';

    protected ?bool $shouldRender = null;

    public function __construct(
        protected ConfigHelper $configHelper,
        protected StoreManagerInterface $storeManager,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function shouldRender(): bool
    {
        if ($this->shouldRender === null) {
            $config = $this->configHelper->getConfigs();
            $validated = $config->getCredentials()
                ->getValidated();
            $enabled = $config->getExpressConfig()
                ->getEnabled();
            $screen = $config->getExpressConfig()
                ->onScreen(static::SCREEN);
            $currency = $this->isAllowedCurrency();

            $this->shouldRender = $enabled && $validated && $screen && $currency;
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
        return $this->storeManager->getStore()
            ->getCurrentCurrencyCode() === TwintConstant::CURRENCY;
    }
}
