<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Pairing;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\RequestLog;

class View extends Template
{
    protected $_template = 'Twint_Magento::pairing/view.phtml';

    private Pairing $entity;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function setEntity(Pairing $entity)
    {
        $this->entity = $entity;
    }

    public function isExpress(): ?bool
    {
        return $this?->entity->isExpressCheckout();
    }
}
