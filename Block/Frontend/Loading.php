<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend;

use Magento\Framework\View\Element\Template;

class Loading extends Template
{
    public function getContent(): string
    {
        return '<div id="modal">Loading</div>';
    }
}
