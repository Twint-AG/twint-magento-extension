<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class PaymentService
{
    public function __construct(
        private OrderPaymentRepositoryInterface $repository
    ) {
    }

    public function update(Payment $payment, Transaction $transaction)
    {
        $payment->setLastTransId($transaction->getId());

        $this->repository->save($payment);
    }
}
