<?php

namespace Twint\Magento\Console\Command;

use DateTime;
use Doctrine\DBAL\Exception\DriverException;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Webapi\Exception;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\MonitorService;

class PollCommand extends Command
{
    public const COMMAND = 'twint:poll';

    private ?DateTime $startedAt = null;
    private ?Pairing $pairing = null;

    public function __construct(
        private readonly State $state,
        private readonly PairingRepositoryInterface $repository,
        private readonly Monolog                    $logger,
        private readonly MonitorService             $monitor,
        ?string                                     $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND);
        $this->addArgument('pairing-id', InputArgument::REQUIRED, 'ID (primary key) of existing TWINT pairings');
        $this->setDescription('Monitoring Pairing');
    }


    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws Throwable
     * @throws Exception
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Running");
        $pairingId = $input->getArgument('pairing-id');
        $this->pairing = $this->repository->getByPairingId($pairingId);

        if(!$this->pairing){
            $this->logger->info("TWINT pairing not exist: {$pairingId}");
            $output->writeln("Pairing not exist");

            return 1;
        }

        $this->startedAt = new DateTime();
        $this->state->setAreaCode(Area::AREA_GLOBAL);

        try {
            while (!$this->pairing->isFinished()) {
                $this->logger->info("TWINT monitor: {$pairingId}: {$this->pairing->getVersion()}");
                $this->repository->updateCheckedAt($this->pairing->getId());

                $this->monitor->monitor($this->pairing);

                sleep($this->getInterval());
                $this->pairing = $this->repository->getByPairingId($pairingId);
            }
        }catch (Throwable $e){
            $this->logger->error("TWINT monitor error: {$pairingId} {$e->getMessage()} {$e->getFile()}:{$e->getLine()}");
            return 1;
        }

        return 0;
    }

    /**
     * Regular: first 3m every 5s, afterwards 10s
     * Express: first 10m every 2s, afterwards 10s
     */
    private function getInterval(): int
    {
        $now = new DateTime();
        $interval = $now->diff($this->startedAt);
        $seconds = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->d * 86400);

        if ($this->pairing->isExpress()) {
            return $seconds < 10 * 60 ? 2 : 10;
        }

        return $seconds < 5 * 60 ? 2 : 10;
    }
}
