<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Webapi\Exception;
use Symfony\Component\Process\Process;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Console\Command\PollCommand;
use Twint\Magento\Model\Monitor\ExpressMonitorStatus;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\Express\OrderConvertService;

class MonitorService
{
    public function __construct(
        private readonly PairingRepositoryInterface $pairingRepository,
        private readonly PairingService             $pairingService,
        private readonly OrderConvertService        $convertService,
        private readonly DirectoryList        $directoryList,
        private readonly Monolog          $logger
    )
    {
    }

    /**
     * @param Pairing|string $pairing
     * @return Pairing
     * @throws CouldNotSaveException
     * @throws Exception
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Throwable
     * @throws InputException
     */
    public function monitor(Pairing|string $pairing): Pairing
    {
        if(is_string($pairing)){
            $pairing = $this->pairingRepository->getByPairingId($pairing);
        }

        if ($pairing->isFinished()) {
            return $pairing;
        }

        $cloned = clone $pairing;

        if ($pairing->isExpress()) {
            $status = $this->pairingService->monitorExpress($pairing, $cloned);
            if ($status->paid()) {
                $this->pairingRepository->markAsOrdering($pairing->getId());
                $orderIncrement = $this->convertService->convert(
                    $status->getAdditionalInformation('pairing'),
                    $status->getAdditionalInformation('history'),
                );
                $status->setAdditionalInformation('order', $orderIncrement);

                $this->pairingRepository->markAsPaid((int) $pairing->getId());
            }
        } else {
            $this->pairingService->monitorRegular($pairing, $cloned);
        }

        return $pairing;
    }

    /**
     * @throws Throwable
     */
    public function status(Pairing $pairing): MonitorStatus
    {
        if($pairing->isFinished()){
            return $pairing->toMonitorStatus();
        }

        if(!$pairing->isMonitoring()) {
            try {
                $process = new Process([
                    'php',
                    $this->directoryList->getRoot() . '/bin/magento',
                    PollCommand::COMMAND,
                    $pairing->getPairingId(),
                ]);
                $process->setOptions([
                    'create_new_console' => true,
                ]);
                $process->disableOutput();
                $process->start();
            } catch (Throwable $e) {
                $this->logger->error("TWINT error start monitor: " . $e->getMessage());
                throw $e;
            }
        }

        // Wait for order placing process
        if ($pairing->getIsOrdering()) {
            $time = 0;
            // 100 - Maximum 10s - same as next JS interval
            while ($time < 100) {
                $this->logger->info(
                    "TWINT usleep(0.3) : {$pairing->getPairingId()}  {$pairing->getStatus()} {$pairing->getVersion()}"
                );

                $pairing = $this->pairingRepository->getByPairingId($pairing->getPairingId());

                if ($pairing->isFinished()) {
                    return $pairing->toMonitorStatus();
                }

                usleep(3 * 100000); // Sleep for 300,000 microseconds (0.3 seconds)
                $time += 3;
            }
        }

        return $pairing->toMonitorStatus();
    }
}
