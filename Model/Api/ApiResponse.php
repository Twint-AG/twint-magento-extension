<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Api;

use Twint\Magento\Model\RequestLog;

class ApiResponse
{
    public function __construct(
        private readonly mixed $return,
        private readonly RequestLog $request
    ) {
    }

    public function getRequest(): RequestLog
    {
        return $this->request;
    }

    public function getReturn(): mixed
    {
        return $this->return;
    }
}
