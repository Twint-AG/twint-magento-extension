<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\Monitor\ExpressMonitorStatus;
use Twint\Magento\Model\Monitor\MonitorStatus;
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
            return MonitorStatus::fromValues(true, MonitorStatus::extractStatus($orgPairing));
        }

        if ($orgPairing->isLocked()) {
            return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
        }

        $this->pairingRepository->lock($orgPairing);
        $pairing = clone $orgPairing;

        if ($orgPairing->isExpressCheckout()) {
            $status = $this->pairingService->monitorExpress($orgPairing, $pairing);
            if ($status->paid()) {
                $orderIncrement = $this->convertService->convert(
                    $status->getAdditionalInformation('pairing'),
                    $status->getAdditionalInformation('history'),
                );
                $status->setAdditionalInformation('order', $orderIncrement);
            }
        } else {
            $status = $this->pairingService->monitorRegular($orgPairing, $pairing);
        }

        $this->pairingRepository->unlock($orgPairing);

        return $status;
    }
}
