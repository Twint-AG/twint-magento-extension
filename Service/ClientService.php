<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;

class ClientService
{
    public function __construct(
        private readonly ClientBuilder $connector,
        private readonly PairingService $pairingService,
        private readonly ApiService $api,
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * @return array
     */
    public function createOrder(InfoInterface $payment, $amount)
    {
        /** @var Payment $payment */
        $storeCode = $payment->getOrder()
            ->getStore()
            ->getCode();
        $client = $this->connector->build($storeCode, Version::LATEST);

        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        $res = $this->api->call($client, 'startOrder', [
            new UnfiledMerchantTransactionReference($orderId),
            new Money(Money::CHF, 1),
        ]);

        $twintOrder = $res->getReturn();

        list($pairing, $history) = $this->pairingService->create($amount, $res, $payment);
        $this->orderService->markAsPendingPayment($order);

        return [$twintOrder, $pairing, $history];
    }

    public function refund(string $pairingId, string $reversalReference, float $amount, int $storeId): ApiResponse
    {
        $client = $this->connector->build($storeId);

        return $this->api->call($client, 'reverseOrder', [
            new UnfiledMerchantTransactionReference($reversalReference),
            new OrderId(new Uuid($pairingId)),
            Money::CHF($amount),
        ]);
    }
}
