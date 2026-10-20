<?php

declare(strict_types=1);

namespace Twint\Magento\Cron;

use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Service\MonitorService;

class UpdatePairingStatus
{
    public function __construct(
        private readonly PairingRepositoryInterface $repository,
        private readonly MonitorService $monitorService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $pairings = $this->repository->getUnFinishes();
        $expressPairings = $this->repository->getUnFinishedExpresses();

        foreach ([$pairings, $expressPairings] as $list) {
            /** @var array $pairing */
            foreach ($list->getItems() as $pairing) {
                try {
                    $this->monitorService->monitor($pairing['pairing_id']);
                } catch (Throwable $e) {
                    $this->logger->error('Cron UpdatePairingStatus: ' . $e->getMessage());
                }
            }
        }
    }
}
