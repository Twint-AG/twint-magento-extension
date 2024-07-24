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
use Twint\Magento\Controller\Regular\BaseAction;
use Twint\Magento\Service\MonitorService;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context                         $context,
        private readonly MonitorService $monitorService
    )
    {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws \Throwable
     * @throws LocalizedException
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $id = $this->getRequest()->getParam('id') ?? null;

        if (empty($id)) {
            throw new UnexpectedValueException("Pairing Id is required");
        }

        return $json->setData(['finish' => $this->monitorService->monitor($id)]);
    }
}
