<?php

declare(strict_types=1);

namespace Twint\Magento\Validator\Certificate;

class CertificateFileValidator
{
    public const ALLOWED_EXT = 'application/x-pkcs12';

    public const ALLOWED_SIZE = 5000; //Kb

    public const ERR_BLANK = 'Certificate file is required';

    public const ERR_INVALID_EXT = 'Only allow file in .p12 format';

    public const ERR_INVALID_SIZE = 'Maximum file size is 5Mb';

    public function validate(array $file)
    {
        if ($file === []) {
            return self::ERR_BLANK;
        }

        if ($file['type'] !== self::ALLOWED_EXT) {
            return self::ERR_INVALID_EXT;
        }

        if ($file['size'] > self::ALLOWED_SIZE) {
            return self::ERR_INVALID_SIZE;
        }

        return true;
    }
}
