<?php

declare(strict_types=1);

namespace Twint\Magento\Exception;

use RuntimeException;
use Throwable;

class CheckoutException extends RuntimeException
{
    public function __construct(string $message = 'TWINT checkout exception', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
