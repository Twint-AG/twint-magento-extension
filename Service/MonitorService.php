<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\MonitorStatus;
use Twint\Magento\Service\Express\OrderConvertService;

class MonitorService
{
    public function __construct(
        private readonly PairingRepositoryInterface $pairingRepository,
        private readonly PairingService             $pairingService,
        private readonly OrderConvertService        $convertService,
    )
    {
    }

    /**
     * @param string $id
     * @return MonitorStatus
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws Throwable
     */
    public function monitor(string $id): MonitorStatus
    {
        $orgPairing = $this->pairingRepository->getByPairingId($id);

        if ($orgPairing->isFinish()) {
            return MonitorStatus::fromBool(true);
        }

        if ($orgPairing->isLocked()) {
            return MonitorStatus::fromBool(false);
        }

        $this->pairingRepository->lock($orgPairing);
        $pairing = clone $orgPairing;

        if ($orgPairing->isExpressCheckout()) {
            list($finished, $pairing, $history) = $this->pairingService->monitorExpress($orgPairing, $pairing);
            if ($finished) {
                $orderIncrement = $this->convertService->convert($pairing, $history);
            }
        } else {
            $finished = $this->pairingService->monitorRegular($orgPairing, $pairing);
        }

        $this->pairingRepository->unlock($orgPairing);

        return new MonitorStatus($finished, $orderIncrement ?? '');
    }
}
