<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
        private readonly TransactionFactory             $factory,
    ) {
    }

    public function createCapture(Order $order, Pairing $pairing, PairingHistory $history): Transaction
    {
        $payment = $order->getPayment();

        /** @var Transaction $transaction */
        $transaction = $this->factory->create();

        $transaction->setPaymentId($payment->getId());
        $transaction->set($payment->getId());
        $transaction->setIsClosed(0);
        $transaction->setTxnId($pairing->getPairingId() . '-' . $history->getId());
        $transaction->setOrderId($order->getId());
        $transaction->setParentTxnId($payment->getLastTransId());
        $transaction->setTxnType(TransactionInterface::TYPE_CAPTURE);
        $transaction->setIsClosed(1);

        return $this->repository->save($transaction);
    }

    public function createVoid(Order $order, Pairing $pairing, PairingHistory $history): Transaction
    {
        $payment = $order->getPayment();

        /** @var Transaction $transaction */
        $transaction = $this->factory->create();

        $transaction->setPaymentId($payment->getId());
        $transaction->set($payment->getId());
        $transaction->setIsClosed(0);
        $transaction->setTxnId($pairing->getPairingId() . '-' . $history->getId());
        $transaction->setOrderId($order->getId());
        $transaction->setParentTxnId($payment->getLastTransId());
        $transaction->setTxnType(TransactionInterface::TYPE_VOID);
        $transaction->setIsClosed(1);

        return $this->repository->save($transaction);
    }
}
