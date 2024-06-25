<?php

namespace Twint\Magento\Controller\Regular;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\Method\TwintRegularMethod;
use Magento\Store\Model\StoreManagerInterface;

class Checkout extends Action implements ActionInterface, HttpPostActionInterface
{

    public function __construct(Context                          $context,
                                private Session                  $session,
                                private OrderRepositoryInterface $orderRepository,
                                private readonly PairingRepositoryInterface $repository,
                                private readonly StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $order = $this->session->getLastRealOrder();
        if(!$order){
            throw new LocalizedException(__("Dont have needed order to pay"));
        }

        $payment = $order->getPayment();
        if(!$payment || $payment->getMethod() != TwintRegularMethod::CODE){
            throw new LocalizedException(__("This order did not provided by TWINT"));
        }

        $data = [
            'token' => $payment->getAdditionalInformation()['qrToken'],
            'orderNumber' => $order->getIncrementId(),
            'storeName' => $this->storeManager->getStore()->getName()
        ];

        return $json->setData($data);
    }
}
