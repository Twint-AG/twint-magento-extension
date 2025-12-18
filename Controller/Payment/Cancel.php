<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Twint\Magento\Controller\Regular\BaseAction;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingRepository;
use Twint\Magento\Service\PairingService;
use Twint\Magento\Util\CryptoHandler;

class Cancel extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private readonly PairingService $pairingService,
        private readonly CryptoHandler $cryptoHandler,
        private readonly PairingRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws Throwable
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

            if (empty($id)) {
                throw new BadRequestHttpException('Pairing Id is required');
            }

            $step = 'decrypt_id';
            $id = $this->cryptoHandler->unHash($id);

            $step = 'load_pairing';
            $pairing = $this->repository->getByPairingId($id);
            if (!$pairing instanceof Pairing) {
                throw new NotFoundHttpException('Pairing not found');
            }

            $step = 'cancel_pairing';
            $success = $this->pairingService->cancel($pairing);

            return $json->setData([
                'success' => $success,
            ]);
        } catch (HttpException $e) {
            return $json->setHttpResponseCode($e->getCode())->setData([
                'success' => false,
                'errorMessage' => $e->getMessage(),
                'step' => $step,
            ]);
        } catch (Throwable $e) {
            $this->logger->error(
                "[TWINT] Payment Cancel error: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}",
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
