<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Twint_Magento',
        '/var/www/dev/magento2/vendor/twint-ag/twint-magento-extension' // for symlink in locally
//    __DIR__
);
