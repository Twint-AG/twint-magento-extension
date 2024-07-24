<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Regular;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
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

    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $id = $this->getRequest()
                ->getParam('id') ?? null;

        return $json->setData([
            'finish' => $this->monitorService->monitor($id),
        ]);
    }
}
