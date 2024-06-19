<?php

namespace Twint\Core\Controller\Regular;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class Checkout extends Action implements ActionInterface, HttpPostActionInterface
{

    public function __construct(Context                          $context,
                                private Session                  $session,
                                private OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        dd($this->getRequest()->getParams());
        $orderId = $this->request->getPost('orderId');
        dd($orderId);
        if(empty($orderId)){
            throw new LocalizedException(__("Order not found"));
        }

        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        $qoute = $this->session->getQuote();
        dd($payment, $orderId);


        return $json->setData([
            'pairingToken' => 123,
            'order' => [],
        ]);
    }
}
