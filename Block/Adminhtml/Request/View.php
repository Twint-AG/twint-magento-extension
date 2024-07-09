<?php

namespace Twint\Magento\Block\Adminhtml\Request;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;

class View extends Template
{
    protected $_template = 'Twint_Magento::request/view.phtml';

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
}
