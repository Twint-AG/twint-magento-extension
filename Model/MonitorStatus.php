<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

class MonitorStatus
{
    public function __construct(
        private bool    $finished,
        private ?string $orderIncrement = null
    )
    {
    }

    public function getFinished(): bool
    {
        return $this->finished;
    }

    public function getOrderIncrement(): ?string
    {
        return $this->orderIncrement;
    }

    public static function fromBool(bool $value): static
    {
        return new static($value);
    }
}
