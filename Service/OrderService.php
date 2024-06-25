<?php

namespace Twint\Magento\Service;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Twint\Magento\Model\Pairing;

class OrderService
{
    public function __construct(
        private readonly Session                         $checkoutSession,
        private readonly OrderRepositoryInterface        $repository,
        private readonly OrderPaymentRepositoryInterface $paymentRepository,
        private readonly SearchCriteriaBuilder           $criteriaBuilder,
    )
    {
    }

    public function pay(Pairing $pairing): void
    {
        $order = $this->getOrder($pairing->getOrderId());

        $order->setState('complete');
        $order->setTotalPaid($pairing->getAmount());
        $order->setBaseTotalPaid($pairing->getAmount());
        $order->setTotalDue(0);
        $order->setBaseTotalDue(0);

        $this->repository->save($order);

        $payment = $order->getPayment();

        $payment->setAmountPaid($pairing->getAmount());
        $payment->setBaseAmountPaid($pairing->getAmount());
        $payment->setShippingCaptured($order->getShippingAmount());
        $payment->setBaseShippingCaptured($order->getBaseShippingAmount());
        $this->paymentRepository->save($payment);
    }

    public function cancel(Pairing $pairing): void
    {
        $order = $this->getOrder($pairing->getOrderId());

        try {
            $order->cancel()->save();
            $this->checkoutSession
                ->unsLastQuoteId()
                ->unsLastSuccessQuoteId()
                ->unsLastOrderId()
                ->unsLastRealOrderId();
        } catch
        (\Throwable $e) {
            throw new LocalizedException(__('Unable to cancel Checkout' . $e->getMessage()));
        }
    }

    protected function getOrder(string $incrementId)
    {
        $results = $this->repository->getList(
            $this->criteriaBuilder->addFilter('increment_id', $incrementId, 'eq')->create()
        );
        return current($results->getItems());
    }
}
