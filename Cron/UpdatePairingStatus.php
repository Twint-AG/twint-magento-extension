<?php

declare(strict_types=1);

namespace Twint\Magento\Cron;

use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Service\PairingService;

class UpdatePairingStatus
{
    public function __construct(
        private readonly PairingRepositoryInterface $repository,
        private readonly PairingService $pairingService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $pairings = $this->repository->getUnFinishes();

        /** @var array $pairing */
        foreach ($pairings->getItems() as $pairing) {
            try {
                $this->pairingService->monitor($pairing['pairing_id']);
            } catch (Throwable $e) {
                $this->logger->error('Cron UpdatePairingStatus: ' . $e->getMessage());
            }
        }
    }
}
