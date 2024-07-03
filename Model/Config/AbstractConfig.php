<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Config;

abstract class AbstractConfig
{
    public function __construct(
        protected array $data
    ) {
    }
}
