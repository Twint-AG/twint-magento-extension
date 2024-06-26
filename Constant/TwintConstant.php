<?php

declare(strict_types=1);

namespace Twint\Magento\Constant;

class TwintConstant
{
    public const SECTION = 'twint';

    public const CONFIG_CREDENTIALS = self::SECTION . '/credentials';

    public const CONFIG_MERCHANT_ID = self::CONFIG_CREDENTIALS . '/merchant_id';

    public const CONFIG_TEST_MODE = self::CONFIG_CREDENTIALS . '/test_mode';

    public const CONFIG_CERTIFICATE = self::CONFIG_CREDENTIALS . '/certificate';

    public const CURRENCY = 'CHF';
}
