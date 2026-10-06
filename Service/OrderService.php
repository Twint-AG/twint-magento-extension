<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Magento\Model\Pairing;

class OrderService
{
    public function __construct(
        private readonly Session $checkoutSession,
        private readonly OrderRepositoryInterface $repository,
        private readonly OrderPaymentRepositoryInterface $paymentRepository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly OrderSender $orderSender,
        private readonly Emulation $emulate,
        private readonly ResolverInterface $localeResolver,
        private readonly LoggerInterface $logger,
        private readonly RendererInterface $phraseRenderer
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
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);

        if (!$order->getEmailSent()) {
            $storeId = (int) $order->getStoreId();
            try {
                // Emulate the store's environment to ensure the correct language is applied
                $this->emulate->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                // switch locale to store locale
                $this->localeResolver->emulate($storeId);
                Phrase::setRenderer($this->phraseRenderer); // apply locale for version <= 2.4.6
                $this->orderSender->send($order, true);
            } catch (Throwable $e) {
                $this->logger->critical($e);
            } finally {
                // Stop the emulation after sending the email
                $this->emulate->stopEnvironmentEmulation();
                $this->localeResolver->revert();
            }
        }

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
     * @throws Exception
     */
    public function cancel(Pairing $pairing, Transaction $transaction): Order
    {
        /** @var Order $order */
        $order = $this->getOrder($pairing->getOrderId());

        $payment = $order->getPayment();
        $payment->setLastTransId($transaction->getTxnId());
        $this->paymentRepository->save($payment);

        $order = $order->cancel()
            ->save();

        $this->checkoutSession
            ->unsLastQuoteId()
            ->unsLastSuccessQuoteId()
            ->unsLastOrderId()
            ->unsLastRealOrderId();

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

    protected function getBaseAmount(Order $order, float $amount): float
    {
        $rate = $order->getBaseToOrderRate() ?? 1;

        return $this->priceCurrency->round($amount / $rate);
    }
}
