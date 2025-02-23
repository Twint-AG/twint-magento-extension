<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\RefundHistory;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;

class View extends Template
{
    protected $_template = 'Twint_Magento::refund_history/view.phtml';

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
}
