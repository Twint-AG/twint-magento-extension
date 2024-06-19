<?php

namespace Twint\Core\Service;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Twint\Core\Api\PairingRepositoryInterface;
use Twint\Core\Builder\ClientBuilder;
use Twint\Core\Model\PairingFactory;
use Twint\Core\Model\Pairing;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Version;

class PaymentService{
    public function __construct(
        private readonly ClientBuilder     $connector,
        private readonly PairingFactory             $pairingFactory,
        private readonly PairingRepositoryInterface $pairingRepository
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

            $this->createPairing($twintOrder, $payment);

            return $twintOrder;
        }catch (\Throwable $e){
            dd($e);
        } finally {

            //write logs
        }
    }

    protected function createPairing(Order $twintOrder, InfoInterface $payment)
    {
        /** @var Pairing $entity */
        $entity = $this->pairingFactory->create();
        $entity->setData('pairing_id', (string) $twintOrder->id());
        $entity->setData('status', (string) $twintOrder->pairingStatus());
        $entity->setData('token', (string) $twintOrder->pairingToken());
        $entity->setData('transaction_status', (string) $twintOrder->transactionStatus());

        /** @var Payment $payment */
        dd($twintOrder->merchantTransactionReference(), $payment->getOrder()->getEntityId());
        $entity->setData('order_id', (int) ((string) $twintOrder->merchantTransactionReference()));
        $entity->setData('store_id', $payment->getOrder()->getStore()->getId());

        return $this->pairingRepository->save($entity);
    }
}
