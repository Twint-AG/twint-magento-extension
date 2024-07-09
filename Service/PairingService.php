<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Throwable;
use Twint\Magento\Api\PairingHistoryRepositoryInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingFactory;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Model\PairingHistoryFactory;
use Twint\Magento\Model\RequestLog;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;

class PairingService
{
    public function __construct(
        private readonly ClientBuilder $connector,
        private readonly PairingFactory $pairingFactory,
        private readonly PairingHistoryFactory $historyFactory,
        private readonly PairingRepositoryInterface $pairingRepository,
        private readonly PairingHistoryRepositoryInterface $historyRepository,
        private readonly OrderService $orderService,
        private readonly ApiService $api,
        private readonly TransactionService $transactionService,
        private readonly InvoiceService $invoiceService
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Throwable
     */
    public function monitor(string $id): bool
    {
        $pairingOrg = $this->pairingRepository->getByPairingId($id);
        if ($pairingOrg->isLocked()) {
            return false;
        }

        $this->pairingRepository->lock($pairingOrg);

        $pairing = clone $pairingOrg;
        $client = $this->connector->build($pairing->getStoreId(), Version::LATEST);

        if ($pairing->isFinish()) {
            return true;
        }

        try {
            $res = $this->api->call(
                $client,
                'monitorOrder',
                [new OrderId(new Uuid($pairing->getPairingId()))],
                false
            );
        } catch (Throwable $e) {
            throw new LocalizedException(__('Cannot get pairing status'));
        }

        /** @var Order $tOrder */
        $tOrder = $res->getReturn();

        if ($tOrder->pairingStatus()->__toString() !== $pairing->getPairingStatus()
            || $tOrder->transactionStatus()->__toString() !== $pairing->getTransactionStatus()
            || $tOrder->status()->__toString() !== $pairing->getStatus()) {

            $log = $this->api->saveLog($res->getRequest());
            $pairing = $this->update($pairing, $tOrder);
            $history = $this->createHistory($pairing, $log);
        }

        if ($tOrder->isPending()) {
            return false;
        }

        if ($tOrder->isSuccessful() && !$pairingOrg->isSuccessful()) {
            $order = $this->orderService->getOrder($pairing->getOrderId());
            $transaction = $this->transactionService->createCapture($order, $pairing, $history);
            $this->orderService->pay($pairing, $transaction);
            $invoice = $this->invoiceService->create($order, $transaction);
        }

        if ($tOrder->isFailure() && !$pairingOrg->isFailure()) {
            $order = $this->orderService->getOrder($pairing->getOrderId());
            $transaction = $this->transactionService->createVoid($order, $pairing, $history);
            $this->orderService->cancel($pairing, $transaction);
        }

        $this->pairingRepository->unlock($pairingOrg);

        return true;
    }

    public function update(Pairing $pairing, Order $order)
    {
        $pairing->setData('status', (string) $order->status());
        $pairing->setData('transaction_status', (string) $order->transactionStatus());
        $pairing->setData('pairing_status', (string) $order->pairingStatus());

        return $this->pairingRepository->save($pairing);
    }

    public function create($amount, ApiResponse $response, InfoInterface $payment): array
    {
        /** @var Order $twintOrder */
        $twintOrder = $response->getReturn();

        /** @var Pairing $pairing */
        $pairing = $this->pairingFactory->create();
        $pairing->setData('pairing_id', (string) $twintOrder->id());
        $pairing->setData('status', (string) $twintOrder->status());
        $pairing->setData('token', (string) $twintOrder->pairingToken());
        $pairing->setData('transaction_status', (string) $twintOrder->transactionStatus());
        $pairing->setData('pairing_status', (string) $twintOrder->pairingStatus());
        $pairing->setData('amount', $amount);

        $pairing->setData('order_id', (string) $twintOrder->merchantTransactionReference());
        $pairing->setData('store_id', $payment->getOrder()->getStore()->getId());

        $pairing = $this->pairingRepository->save($pairing);

        $history = $this->createHistory($pairing, $response->getRequest());

        return [$pairing, $history];
    }

    public function createHistory(Pairing $pairing, RequestLog $log): PairingHistory
    {
        /** @var PairingHistory $history */
        $history = $this->historyFactory->create();
        $history->setData('parent_id', (string) $pairing->getId());
        $history->setData('status', $pairing->getStatus());
        $history->setData('transaction_status', $pairing->getTransactionStatus());
        $history->setData('pairing_status', $pairing->getPairingStatus());
        $history->setData('token', $pairing->getToken());
        $history->setData('amount', $pairing->getAmount());
        $history->setData('store_id', $pairing->getStoreId());
        $history->setData('order_id', $pairing->getOrderId());
        $history->setData('request_id', $log->getId());

        return $this->historyRepository->save($history);
    }
}
