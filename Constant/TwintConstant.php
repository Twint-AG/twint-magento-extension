<?php

declare(strict_types=1);

namespace Twint\Magento\Constant;

class TwintConstant
{
    public const SECTION = 'twint';

    public const CONFIG_CREDENTIALS = self::SECTION . '/credentials';

    public const CONFIG_MERCHANT_ID = self::CONFIG_CREDENTIALS . '/merchant_id';

    public const CONFIG_TEST_MODE = self::CONFIG_CREDENTIALS . '/environment';

    public const CONFIG_CERTIFICATE = self::CONFIG_CREDENTIALS . '/certificate';

    public const CONFIG_VALIDATED = self::CONFIG_CREDENTIALS . '/validated';

    public const CURRENCY = 'CHF';

    public const REGULAR_ENABLED = 'twint/regular/enabled';

    public const EXPRESS_ENABLED = 'twint/regular/enabled';
    public const EXPRESS_SCREENS = 'twint/express/screens';

    public const SCREEN_PLP = 'PLP';
    public const SCREEN_PDP = 'PDP';
    public const SCREEN_CART = 'CART';
    public const SCREEN_CART_FLYOUT = 'CART_FLYOUT';
}
