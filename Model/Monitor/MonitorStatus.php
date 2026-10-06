<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Monitor;

use Twint\Magento\Model\Pairing;

class MonitorStatus
{
    public const STATUS_PAID = 1;

    public const STATUS_IN_PROGRESS = 0;

    public const STATUS_CANCELLED = -1;

    public const STATUS_FAILED = -2;

    public function __construct(
        private readonly bool $finished,
        private readonly int $status = self::STATUS_IN_PROGRESS,
        private array $args = []
    ) {
    }

    public static function fromPairing(Pairing $pairing): static
    {
        $instance = new static($pairing->isFinished(), self::extractStatus($pairing));

        if ($instance->status === self::STATUS_FAILED) {
            $instance->args['message'] = __('Payment did not succeed, please try another payment method.');
        }

        if ($instance->status === self::STATUS_PAID) {
            $instance->args['order'] = $pairing->getOrderId();
        }

        return $instance;
    }

    public static function fromValues(bool $finished, int $status, array $args = []): static
    {
        return new static($finished, $status, $args);
    }

    public static function extractStatus(Pairing $pairing): int
    {
        if ($pairing->isSuccessful()) {
            return self::STATUS_PAID;
        }
        if ($pairing->isCancelled()) {
            return self::STATUS_CANCELLED;
        }

        if ($pairing->isFailed()) {
            return self::STATUS_FAILED;
        }

        return self::STATUS_IN_PROGRESS;
    }

    public function getFinished(): bool
    {
        return $this->finished;
    }

    public function getAdditionalInformation(string $key): mixed
    {
        return $this->args[$key] ?? null;
    }

    public function setAdditionalInformation(string $key, mixed $value): void
    {
        $this->args[$key] = $value;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function paid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
