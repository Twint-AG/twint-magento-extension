<?php

declare(strict_types=1);

namespace Twint\Magento\Constant;

class TwintConstant
{
    public const PLATFORM = 'Magento';

    public const SECTION = 'twint';

    public const SECTION_EXPRESS = 'twint_express';

    public const CONFIG_CREDENTIALS = self::SECTION . '/credentials';

    public const CONFIG_STORE_UUID = self::CONFIG_CREDENTIALS . '/store_uuid';

    public const CONFIG_TEST_MODE = self::CONFIG_CREDENTIALS . '/environment';

    public const CONFIG_CERTIFICATE = self::CONFIG_CREDENTIALS . '/certificate';

    public const CONFIG_VALIDATED = self::CONFIG_CREDENTIALS . '/validated';

    public const CURRENCY = 'CHF';

    public const REGULAR_ENABLED = 'twint/regular/enabled';

    public const EXPRESS_ENABLED = 'twint/express/enabled';

    public const EXPRESS_SCREENS = 'twint/express/screens';

    public const SCREEN_PLP = 'PLP';

    public const SCREEN_PDP = 'PDP';

    public const SCREEN_CART = 'cart';

    public const SCREEN_CART_FLYOUT = 'minicart';

    public const MONITORING_TIME_WINDOW = 10; //Consider a pairing is under monitoring with in 10 seconds

    public const EXCEPTION_VERSION_CONFLICT = 45000;

    public const PAIRING_TIMEOUT_REGULAR = 60 * 3; //3 mins

    public const PAIRING_TIMEOUT_EXPRESS = 60 * 5; //5 mins

    public const SHIPPING_METHOD_SEPARATOR = '+';
}
