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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Magento\Model\PairingRepository;
use Twint\Magento\Service\MonitorService;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context                         $context,
        private readonly MonitorService $monitorService,
        private readonly PairingRepository $repository
    )
    {
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
        $id = $this->getRequest()
                ->getParam('id') ?? null;

        $pairing = $this->repository->getByPairingId($id);
        if(!$pairing){
            throw new NotFoundHttpException('Pairing not found');
        }

        $status = $this->monitorService->status($pairing);

        return $json->setData([
            'finish' => $status->getFinished(),
            'paid' => $status->getStatus() == MonitorStatus::STATUS_PAID,
        ]);
    }
}
