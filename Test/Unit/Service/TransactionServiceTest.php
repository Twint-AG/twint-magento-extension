<?php

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Service\TransactionService;

class TransactionServiceTest extends MockeryTestCase
{
    private $transactionRepositoryMock;
    private $transactionFactoryMock;
    private $transactionService;

    function tearDown(): void
    {
        Mockery::close();
    }

    protected function setUp(): void
    {
        $this->transactionRepositoryMock = Mockery::mock(TransactionRepositoryInterface::class);
        $this->transactionFactoryMock = Mockery::mock(TransactionFactory::class);

        $this->transactionService = new TransactionService(
            $this->transactionRepositoryMock,
            $this->transactionFactoryMock
        );
    }

    public function testCreateCapture()
    {
        $order = Mockery::mock(Order::class);
        $payment = Mockery::mock(Payment::class);
        $pairing = Mockery::mock(Pairing::class);
        $pairingHistory = Mockery::mock(PairingHistory::class);
        $transaction = Mockery::mock(Transaction::class);

        $this->setupMocksForTransactionCreation($order, $payment, $pairing, $pairingHistory, $transaction);

        $transaction->shouldReceive('setTxnType')
            ->with(TransactionInterface::TYPE_CAPTURE)
            ->once();

        $result = $this->transactionService->createCapture($order, $pairing, $pairingHistory);

        $this->assertSame($transaction, $result);
    }

    public function testCreateVoid()
    {
        $order = Mockery::mock(Order::class);
        $payment = Mockery::mock(Payment::class);
        $pairing = Mockery::mock(Pairing::class);
        $pairingHistory = Mockery::mock(PairingHistory::class);
        $transaction = Mockery::mock(Transaction::class);

        $this->setupMocksForTransactionCreation($order, $payment, $pairing, $pairingHistory, $transaction);

        $transaction->shouldReceive('setTxnType')
            ->with(TransactionInterface::TYPE_VOID)
            ->once();

        $result = $this->transactionService->createVoid($order, $pairing, $pairingHistory);

        $this->assertSame($transaction, $result);
    }

    private function setupMocksForTransactionCreation($order, $payment, $pairing, $pairingHistory, $transaction)
    {
        $order->shouldReceive('getPayment')->once()->andReturn($payment);
        $order->shouldReceive('getId')->once()->andReturn(1);

        $payment->shouldReceive('getId')->twice()->andReturn(100);
        $payment->shouldReceive('getLastTransId')->once()->andReturn('last_trans_id');

        $pairing->shouldReceive('getPairingId')->once()->andReturn('pairing_id');

        $pairingHistory->shouldReceive('getId')->once()->andReturn(1);

        $this->transactionFactoryMock->shouldReceive('create')
            ->once()
            ->andReturn($transaction);

        $transaction->shouldReceive('setPaymentId')->with(100)->once();
        $transaction->shouldReceive('set')->with(100)->once();
        $transaction->shouldReceive('setIsClosed')->with(0)->once();
        $transaction->shouldReceive('setTxnId')->with('pairing_id-1')->once();
        $transaction->shouldReceive('setOrderId')->with(1)->once();
        $transaction->shouldReceive('setParentTxnId')->with('last_trans_id')->once();
        $transaction->shouldReceive('setIsClosed')->with(1)->once();

        $this->transactionRepositoryMock->shouldReceive('save')
            ->with($transaction)
            ->once()
            ->andReturn($transaction);
    }
}
