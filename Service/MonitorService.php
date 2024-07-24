<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
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
     * @return bool
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws Throwable
     */
    public function monitor(string $id): bool
    {
        $orgPairing = $this->pairingRepository->getByPairingId($id);

        if ($orgPairing->isFinish()) {
            return true;
        }

        if ($orgPairing->isLocked()) {
            return false;
        }

        $this->pairingRepository->lock($orgPairing);
        $pairing = clone $orgPairing;

        if ($orgPairing->isExpressCheckout()) {
            list($finish, $pairing, $history) = $this->pairingService->monitorExpress($orgPairing, $pairing);
            if ($finish) {
                $this->convertService->convert($pairing, $history);
            }
        } else {
            $finish = $this->pairingService->monitorRegular($orgPairing, $pairing);
        }

        $this->pairingRepository->unlock($orgPairing);

        return $finish;
    }
}
