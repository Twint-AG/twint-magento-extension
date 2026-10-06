<?php

declare(strict_types=1);

namespace Twint\Magento\Logger;

use Monolog\Logger;

class TwintLogger extends Logger
{
    protected static $levels = [
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    ];
}
