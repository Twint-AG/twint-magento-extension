<?php

namespace Twint\Magento\Service;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\PairingFactory;
use Twint\Magento\Model\Pairing;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Version;

class PaymentService{
    public function __construct(
        private readonly ClientBuilder     $connector,
        private readonly PairingService $pairingService
    )
    {
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return Order|void
     */
    public function createOrder(InfoInterface $payment, $amount){
        /** @var Payment $payment */
        $storeCode = $payment->getOrder()->getStore()->getCode();
        $client = $this->connector->build($storeCode, Version::LATEST);


        try {
            $order = $payment->getOrder();
            $orderId = $order->getIncrementId();

            $twintOrder =  $client->startOrder(
                new UnfiledMerchantTransactionReference($orderId),
                new Money(Money::CHF, $amount)
            );

            $this->pairingService->create($amount, $twintOrder, $payment);

            return $twintOrder;
        }catch (\Throwable $e){
            dd($e);
        } finally {

            //write logs
        }
    }
}
