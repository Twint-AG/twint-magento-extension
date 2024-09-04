<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Express;

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
use Twint\Command\TwintPollCommand;
use Twint\Magento\Controller\Regular\BaseAction;
use Twint\Magento\Model\PairingRepository;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Util\CryptoHandler;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context                         $context,
        private readonly MonitorService $monitorService,
        private readonly CryptoHandler $cryptoHandler,
        private readonly PairingRepository $repository
    )
    {
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
        $id = $this->getRequest()->getParam('id') ?? null;

        if (empty($id)) {
            throw new UnexpectedValueException("Pairing Id is required");
        }

        $id = $this->cryptoHandler->unHash($id);
        $pairing = $this->repository->getByPairingId($id);
        if(!$pairing){
            throw new NotFoundHttpException('Pairing not found');
        }

        $monitorStatus = $this->monitorService->status($pairing);

        return $json->setData([
            'finish' => $monitorStatus->getFinished(),
            'status' => $monitorStatus->getStatus(),
            'order' => $monitorStatus->getAdditionalInformation('order')
        ]);
    }
}
