<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Regular;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingRepository;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Util\CryptoHandler;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private readonly MonitorService $monitorService,
        private readonly PairingRepository $repository,
        private readonly CryptoHandler $cryptoHandler,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws Throwable
     * @throws Exception
     * @throws LocalizedException
     */
    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $step = 'init';
        try {
            $step = 'get_id';
            $id = $this->getRequest()
                ->getParam('id') ?? null;

            $step = 'decrypt_id';
            $id = $this->cryptoHandler->unHash($id);

            $step = 'load_pairing';
            $pairing = $this->repository->getByPairingId($id);
            if (!$pairing instanceof Pairing) {
                throw new NotFoundHttpException('Pairing not found');
            }

            $step = 'monitor_status';
            $status = $this->monitorService->status($pairing);

            $step = 'response';
            return $json->setData([
                'finish' => $status->getFinished(),
                'paid' => $status->getStatus() === MonitorStatus::STATUS_PAID,
            ]);
        } catch (HttpException $e) {
            return $json->setHttpResponseCode($e->getCode())->setData([
                'finish' => true,
                'paid' => false,
                'errorMessage' => $e->getMessage(),
                'step' => $step,
            ]);
        } catch (Throwable $e) {
            $this->logger->error(
                "[TWINT] Regular Status error: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}",
                [
                    'step' => $step,
                    'error' => $e->getMessage(),
                ]
            );

            return $json->setHttpResponseCode($e->getCode())->setData([
                'success' => false,
                'step' => $step,
            ]);
        }
    }
}
