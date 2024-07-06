<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Order;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Text\ListText;
use Twint\Magento\Model\Method\TwintMethod;

class TwintTab extends ListText implements TabInterface
{
    public function __construct(
        Context $context,
        private AuthorizationInterface $authorization,
        private Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function getTabLabel(): Phrase
    {
        return __('TWINT');
    }

    public function getTabTitle(): Phrase
    {
        return __('TWINT Tab Title');
    }

    public function canShowTab(): bool
    {
        $instance = $this->getOrder()
            ->getPayment()
            ->getMethodInstance();

        return $instance instanceof TwintMethod;
    }

    public function isHidden(): bool
    {
        return !$this->authorization->isAllowed('Magento_Sales::transactions_fetch');
    }
}
