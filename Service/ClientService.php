<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Webapi\Exception;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Throwable;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Exception\PaymentException;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;

class ClientService
{
    public function __construct(
        private readonly ClientBuilder  $connector,
        private readonly PairingService $pairingService,
        private readonly ApiService     $api,
        private readonly OrderService   $orderService,
        private readonly MonitorService   $monitor,
        private readonly Monolog          $logger
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function startFastCheckoutOrder(InfoInterface $payment, float $amount, Pairing $pairing): array
    {
        $client = $this->connector->build($pairing->getStoreId());

        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();

        $res = $this->api->call($client, 'startFastCheckoutOrder', [
            PairingUuid::fromString($pairing->getPairingId()),
            new UnfiledMerchantTransactionReference($orderId),
            new Money(TwintConstant::CURRENCY, $amount),
        ]);

        /** @var Order $twintOrder */
        $twintOrder = $res->getReturn();

        list($pairing, $history) = $this->pairingService->create($amount, $res, $payment, true);

        $success = $this->monitorOrder($pairing);
        if(!$success){
            throw new PaymentException("TWINT: Your balance is insufficient.");
        }

        return [$twintOrder, $pairing, $history];
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws Throwable
     * @throws Exception
     * @throws LocalizedException
     * @throws InputException
     */
    protected function monitorOrder(Pairing $pairing): bool
    {
        while(!$pairing->isFinished()) {
            $this->logger->info("TWINT EC monitor: {$pairing->getPairingId()} {$pairing->getStatus()} {$pairing->getTransactionStatus()} {$pairing->getPairingStatus()}");
            $pairing = $this->monitor->monitor($pairing);
        }

        return $pairing->isSuccessful();
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return array
     *
     * @throws Throwable
     */
    public function createOrder(InfoInterface $payment, $amount): array
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
            new Money(Money::CHF, $amount),
        ]);

        /** @var Order $twintOrder */
        $twintOrder = $res->getReturn();

        list($pairing, $history) = $this->pairingService->create($amount, $res, $payment);

        $this->orderService->markAsPendingPayment($order);

        $this->monitor->status($pairing);

        return [$twintOrder, $pairing, $history];
    }

    public function refund(string|int $pairingId, string $reversalReference, float $amount, int $storeId): ApiResponse
    {
        $client = $this->connector->build($storeId);

        return $this->api->call($client, 'reverseOrder', [
            new UnfiledMerchantTransactionReference($reversalReference),
            new OrderId(new Uuid($pairingId)),
            Money::CHF($amount),
        ]);
    }
}
