<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote;
use Throwable;
use Twint\Magento\Api\PairingHistoryRepositoryInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingFactory;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Model\PairingHistoryFactory;
use Twint\Magento\Model\RequestLog;
use Twint\Magento\Plugin\SubmitClonedQuotePlugin;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;
use Zend_Db_Statement_Exception;

class PairingService
{
    public function __construct(
        private readonly ClientBuilder                     $connector,
        private readonly PairingFactory                    $pairingFactory,
        private readonly PairingHistoryFactory             $historyFactory,
        private readonly PairingRepositoryInterface        $pairingRepository,
        private readonly PairingHistoryRepositoryInterface $historyRepository,
        private readonly OrderService                      $orderService,
        private readonly ApiService                        $api,
        private readonly TransactionService                $transactionService,
        private readonly InvoiceService                    $invoiceService,
        private readonly CartService                       $cartService,
        private readonly Monolog                           $logger
    )
    {
    }

    /**
     * @throws Throwable
     * @throws LocalizedException
     */
    public function monitorRegular(Pairing $orgPairing, Pairing $pairing): MonitorStatus
    {
        $client = $this->connector->build($pairing->getStoreId());

        try {
            $res = $this->api->call(
                $client,
                'monitorOrder',
                [new OrderId(new Uuid($pairing->getPairingId()))],
                false
            );
        } catch (Throwable $e) {
            $this->logger->error("TWINT cannot get pairing status: " . $e->getMessage());
            throw $e;
        }

        return $this->recursiveMonitor($orgPairing, $pairing, $client, $res);
    }

    /**
     * @throws Throwable
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     */
    protected function recursiveMonitor(Pairing $orgPairing, Pairing $pairing, InvocationRecordingClient $client, ApiResponse $res): MonitorStatus
    {
        /** @var Order $tOrder */
        $tOrder = $res->getReturn();

        if ($pairing->hasDiffs($tOrder)) {
            try {
                $pairing = $this->update($pairing, $tOrder);
            } catch (Zend_Db_Statement_Exception $e) {
                if ($e->getCode() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                    $this->logger->info("TWINT {$pairing->getPairingId()} update was conflicted");
                    return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
                }

                throw $e;
            }

            $log = $res->getRequest();
            if (empty($log->getId())) {
                $log = $this->api->saveLog($res->getRequest());
            }

            $history = $this->createHistory($pairing, $log);
        }

        if ($tOrder->isPending()) {
            if ($tOrder->isConfirmationPending()) {
                $confirmRes = $this->api->call($client, 'confirmOrder', [
                    new UnfiledMerchantTransactionReference($pairing->getOrderId()),
                    new Money(Money::CHF, $pairing->getAmount()),
                ]);

                return $this->recursiveMonitor($orgPairing, $pairing, $client, $confirmRes);
            }

            return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
        }

        /**
         * Only process as paid when:
         * - Did not process before (captured)
         * - First time get status success
         */
        if (!$orgPairing->getCaptured() && $tOrder->isSuccessful() && !$orgPairing->isSuccessful()) {
            $order = $this->orderService->getOrder($pairing->getOrderId());
            $transaction = $this->transactionService->createCapture($order, $pairing, $history);
            $this->orderService->pay($pairing, $transaction);
            $this->invoiceService->create($order, $transaction);
            $pairing = $this->markAsCaptured($pairing);
            $this->cartService->removeAllItems($pairing->getOriginalQuoteId());

            return MonitorStatus::fromValues(true, MonitorStatus::STATUS_PAID);
        }

        if ($tOrder->isFailure() && !$orgPairing->isFailure()) {
            if(!$orgPairing->getCaptured()) {
                $order = $this->orderService->getOrder($pairing->getOrderId());
                $transaction = $this->transactionService->createVoid($order, $pairing, $history);
                $this->orderService->cancel($pairing, $transaction);
            }

            return MonitorStatus::fromValues(true, MonitorStatus::STATUS_CANCELLED);
        }

        return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
    }

    private function markAsCaptured(Pairing $pairing): Pairing
    {
        $pairing->setData('capture', 1);

        return $this->pairingRepository->save($pairing);
    }

    /**
     * @param Pairing $pairing
     * @param Pairing $cloned
     * @return MonitorStatus
     * @throws Throwable
     */
    public function monitorExpress(Pairing $pairing, Pairing $cloned): MonitorStatus
    {
        $client = $this->connector->build($cloned->getStoreId(), Version::NEXT);

        $res = $this->api->call(
            $client,
            'monitorFastCheckOutCheckIn',
            [PairingUuid::fromString($cloned->getPairingId())],
            false
        );

        /** @var FastCheckoutCheckIn $checkInState */
        $checkInState = $res->getReturn();

        $status = MonitorStatus::STATUS_IN_PROGRESS;
        $finished = false;

        if (!$cloned->hasDiffs($checkInState)) {
            return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
        }

        try {
            $cloned = $this->updateForExpress($cloned, $checkInState);
        } catch (Zend_Db_Statement_Exception $e) {
            if ($e->getCode() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                $this->logger->info("TWINT {$pairing->getPairingId()} update was conflicted");
                return MonitorStatus::fromValues(false, MonitorStatus::STATUS_IN_PROGRESS);
            }

            throw $e;
        }

        $log = $this->api->saveLog($res->getRequest());
        $history = $this->createHistory($cloned, $log);

        if (empty($pairing->getCustomerData()) && $checkInState->hasCustomerData()) {
            $status = MonitorStatus::STATUS_PAID;

            return MonitorStatus::fromValues(true, $status, [
                'pairing' => $cloned,
                'history' => $history
            ]);
        }

        if (!$pairing->getIsOrdering() && $pairing->getPairingStatus() !== PairingStatus::NO_PAIRING && $cloned->getPairingStatus() === PairingStatus::NO_PAIRING && !$checkInState->hasCustomerData()) {
            $this->logger->info("TWINT mark as cancelled {$pairing->getPairingStatus()} - {$cloned->getPairingStatus()}");

            $this->pairingRepository->markAsCancelled((int)$pairing->getId());
            $finished = true;
            $status = MonitorStatus::STATUS_CANCELLED;
        }

        return MonitorStatus::fromValues($finished, $status);
    }

    public function updateForExpress(Pairing $pairing, FastCheckoutCheckIn $checkIn): Pairing
    {
        $pairing->setData('version', $pairing->getVersion());
        $pairing->setData('customer', $checkIn->hasCustomerData() ? json_encode($checkIn->customerData()) : null);
        $pairing->setData('shipping_id', (string)$checkIn->shippingMethodId());
        $pairing->setData('pairing_status', (string)$checkIn->pairingStatus());

        $this->logger->info("TWINT update: {$pairing->getPairingId()} {$pairing->getPairingStatus()}");

        return $this->pairingRepository->save($pairing);
    }

    public function update(Pairing $pairing, Order $order): Pairing
    {
        $pairing->setData('status', (string)$order->status());
        $pairing->setData('transaction_status', (string)$order->transactionStatus());
        $pairing->setData('pairing_status', (string)$order->pairingStatus());

        $this->logger->info("TWINT update: {$pairing->getPairingId()} {$pairing->getTransactionStatus()} {$pairing->getPairingStatus()}");

        return $this->pairingRepository->save($pairing);
    }

    public function create($amount, ApiResponse $response, InfoInterface $payment, bool $captured = false): array
    {
        /** @var Order $twintOrder */
        $twintOrder = $response->getReturn();

        $pairing = $this->pairingFactory->create();
        $pairing->setData('pairing_id', (string)$twintOrder->id());
        $pairing->setData('status', (string)$twintOrder->status());
        $pairing->setData('token', (string)$twintOrder->pairingToken());
        $pairing->setData('transaction_status', (string)$twintOrder->transactionStatus());
        $pairing->setData('pairing_status', (string)$twintOrder->pairingStatus());
        $pairing->setData('amount', $amount);

        $pairing->setData('order_id', (string)$twintOrder->merchantTransactionReference());
        $pairing->setData('store_id', $payment->getOrder()->getStore()->getId());

        $pairing->setData('captured', (int)$captured);

        if ($pair = SubmitClonedQuotePlugin::$pair) {
            $pairing->setData('org_quote_id', $pair[0]->getId());
            $pairing->setData('quote_id', $pair[1]->getId());
        }

        $pairing = $this->pairingRepository->save($pairing);

        $history = $this->createHistory($pairing, $response->getRequest());

        return [$pairing, $history];
    }

    public function createForExpress(ApiResponse $response, Quote $quote, Quote $orgQuote): array
    {
        /** @var InteractiveFastCheckoutCheckIn $checkIn */
        $checkIn = $response->getReturn();

        $pairing = $this->pairingFactory->create();
        $pairing->setData('pairing_id', (string)$checkIn->pairingUuid());
        $pairing->setData('token', (string)$checkIn->pairingToken());
        $pairing->setData('pairing_status', (string)$checkIn->pairingStatus());
        $pairing->setData('amount', $quote->getGrandTotal());
        $pairing->setData('store_id', $quote->getStoreId());
        $pairing->setData('quote_id', $quote->getId());
        $pairing->setData('org_quote_id', $orgQuote->getId());
        $pairing->setData('is_express', true);

        $pairing = $this->pairingRepository->save($pairing);

        $history = $this->createHistory($pairing, $response->getRequest());

        return [$pairing, $history];
    }

    public function createHistory(Pairing $pairing, RequestLog $log, float $amount = null): PairingHistory
    {
        $history = $this->historyFactory->create();
        $history->setData('parent_id', (string)$pairing->getId());
        $history->setData('status', $pairing->getStatus());
        $history->setData('transaction_status', $pairing->getTransactionStatus());
        $history->setData('pairing_status', $pairing->getPairingStatus());
        $history->setData('token', $pairing->getToken());
        $history->setData('amount', $amount ?? $pairing->getAmount());
        $history->setData('store_id', $pairing->getStoreId());
        $history->setData('order_id', $pairing->getOrderId());
        $history->setData('org_quote_id', $pairing->getOriginalQuoteId());
        $history->setData('quote_id', $pairing->getQuoteId());
        $history->setData('shipping_id', $pairing->getShippingId());
        $history->setData('customer', $pairing->getCustomerData());
        $history->setData('request_id', $log->getId());
        $history->setData('captured', (int)$pairing->getCaptured());

        return $this->historyRepository->save($history);
    }

    public function appendOrderId(int|string $quoteId, string $orderId): void
    {
        $this->pairingRepository->updateOrderId($orderId, $quoteId);
        $this->historyRepository->updateOrderId($orderId, $quoteId);
    }
}
