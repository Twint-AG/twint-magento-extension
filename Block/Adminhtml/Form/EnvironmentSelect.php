<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Twint\Magento\Helper\ConfigHelper;
use Twint\Sdk\Value\Environment as Option;

class EnvironmentSelect extends Select
{
    public const TWINT_TEST_MODE = 'showTwintEnvOptions';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ConfigHelper $configHelper,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
        ?Random $random = null
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
    }

    public function getHtml(): string
    {
        if (!$this->shouldDisplayTestingMode()) {
            return str_replace('<tr', '<tr style="display: none;"', parent::getHtml());
        }

        return parent::getHtml();
    }

    public function shouldDisplayTestingMode(): bool
    {
        if ($this->request->getParam(self::TWINT_TEST_MODE) === '0') {
            return false;
        }

        return $this->request->getParam(
            self::TWINT_TEST_MODE
        ) === '1' || $this->configHelper->getEnvironment() === Option::TESTING;
    }
}
