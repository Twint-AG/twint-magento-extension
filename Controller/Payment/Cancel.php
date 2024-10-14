<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Payment;

use Http\Message\Exception\UnexpectedValueException;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
        private readonly PairingRepository $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws Throwable
     * @throws LocalizedException
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $id = $this->getRequest()
            ->getParam('id') ?? null;

        if (empty($id)) {
            throw new UnexpectedValueException('Pairing Id is required');
        }

        $id = $this->cryptoHandler->unHash($id);
        $pairing = $this->repository->getByPairingId($id);
        if (!$pairing instanceof Pairing) {
            throw new NotFoundHttpException('Pairing not found');
        }

        return $json->setData([
            'success' => $this->pairingService->cancel($pairing),
        ]);
    }
}
