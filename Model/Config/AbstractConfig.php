<?php

namespace Twint\Magento\Model\Config;

abstract  class AbstractConfig
{
    public function __construct(protected array $data)
    {
    }
}
