<?php

namespace Twint\Core\Model\Config;

abstract  class AbstractConfig
{
    public function __construct(protected array $data)
    {
    }
}
