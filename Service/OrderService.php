<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Throwable;
use Twint\Magento\Model\Pairing;

class OrderService
{
    public function __construct(
        private readonly Session $checkoutSession,
        private readonly OrderRepositoryInterface $repository,
        private readonly OrderPaymentRepositoryInterface $paymentRepository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    public function pay(Pairing $pairing, Transaction $transaction): Order
    {
        /** @var Order $order */
        $order = $this->getOrder($pairing->getOrderId());

        $order->setTotalPaid($pairing->getAmount());
        $order->setBaseTotalPaid($this->getBaseAmount($order, $pairing->getAmount()));
        $order->setTotalDue(0);
        $order->setBaseTotalDue(0);

        $order->addCommentToStatusHistory(
            __('Captured amount of CHF %1, Transaction ID %2', $pairing->getAmount(), $transaction->getTxnId()),
            $order->getStatus()
        );

        $this->repository->save($order);

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        $payment->setAmountPaid($pairing->getAmount());
        $payment->setBaseAmountPaid($pairing->getAmount());
        $payment->setShippingCaptured($order->getShippingAmount());
        $payment->setBaseShippingCaptured($order->getBaseShippingAmount());
        $payment->setTransactionId($transaction->getTxnId());
        $payment->setLastTransId($transaction->getTxnId());
        $this->paymentRepository->save($payment);

        return $order;
    }

    /**
     * @throws LocalizedException
     */
    public function cancel(Pairing $pairing, Transaction $transaction): Order
    {
        /** @var Order $order */
        $order = $this->getOrder($pairing->getOrderId());

        $payment = $order->getPayment();
        $payment->setLastTransId($transaction->getTxnId());
        $this->paymentRepository->save($payment);

        try {
            $order = $order->cancel()
                ->save();
            $this->checkoutSession
                ->unsLastQuoteId()
                ->unsLastSuccessQuoteId()
                ->unsLastOrderId()
                ->unsLastRealOrderId();
        } catch (Throwable $e) {
            throw new LocalizedException(__('Unable to cancel Checkout' . $e->getMessage()));
        }

        return $order;
    }

    public function getOrder(string $incrementId)
    {
        $results = $this->repository->getList(
            $this->criteriaBuilder->addFilter('increment_id', $incrementId, 'eq')
                ->create()
        );
        return current($results->getItems());
    }

    public function markAsPendingPayment(Order $order): Order
    {
        $order->setState(Order::STATE_PENDING_PAYMENT);

        return $this->repository->save($order);
    }

    public function updateRefundedAmount(string $orderIncrement, float $amount): OrderInterface
    {
        /** @var Order $order */
        $order = $this->getOrder($orderIncrement);
        $order->setTotalRefunded($order->getTotalRefunded() + $amount);

        return $this->repository->save($order);
    }

    protected function getBaseAmount(Order $order, float $amount): float
    {
        $rate = $order->getBaseToOrderRate() ?? 1;

        return $this->priceCurrency->round($amount / $rate);
    }
}
