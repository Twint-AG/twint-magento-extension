<?php

namespace Twint\Magento\Controller\Regular;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Service\PairingService;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(Context                                $context,
                                private readonly StoreManagerInterface $storeManager,
                                private readonly PairingService $pairingService
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $id = $this->getRequest()->getParam('id') ?? null;

        return $json->setData([
            'finish' => $this->pairingService->monitor($id)
        ]);
    }
}
