<?php

namespace Twint\Magento\Service;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingFactory;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;

class PairingService
{
    public function __construct(
        private readonly StoreManagerInterface      $storeManager,
        private readonly ClientBuilder              $connector,
        private readonly PairingFactory             $pairingFactory,
        private readonly PairingRepositoryInterface $pairingRepository,
        private readonly OrderService $orderService
    )
    {
    }


    public function monitor(string $id): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        $client = $this->connector->build($storeCode, Version::LATEST);

        $pairing = $this->pairingRepository->getByPairingId($id);

        // prevent spam request to TWINT and reduce risk about parallel processing
        if ($pairing->getLock()) {
            return false;
        }

        if ($pairing->isFinish())
            return true;

        try {
            $tOrder = $client->monitorOrder(new OrderId(new Uuid((string)$pairing->getPairingId())));
        } catch (\Throwable $e) {

        }

        if ($tOrder->isPending()) {
            return false;
        }

        if ($tOrder->isSuccessful()) {
            $this->update($pairing, $tOrder);
            $this->orderService->pay($pairing);
        }

        if ($tOrder->isFailure()) {
            $this->update($pairing, $tOrder);
            $this->orderService->cancel($pairing);
        }

        $this->pairingRepository->unlock($pairing);

        return true;
    }

    public function update(Pairing $pairing, Order $order)
    {
        $pairing->setData('status', (string)$order->pairingStatus());
        $pairing->setData('transaction_status', (string)$order->transactionStatus());

        return $this->pairingRepository->save($pairing);
    }

    public function create($amount, Order $twintOrder, InfoInterface $payment)
    {
        /** @var Pairing $entity */
        $entity = $this->pairingFactory->create();
        $entity->setData('pairing_id', (string)$twintOrder->id());
        $entity->setData('status', (string)$twintOrder->pairingStatus());
        $entity->setData('token', (string)$twintOrder->pairingToken());
        $entity->setData('transaction_status', (string)$twintOrder->transactionStatus());
        $entity->setData('amount', $amount);

        /** @var Payment $payment */
        $entity->setData('order_id', (string)$twintOrder->merchantTransactionReference());
        $entity->setData('store_id', $payment->getOrder()->getStore()->getId());

        return $this->pairingRepository->save($entity);
    }
}
