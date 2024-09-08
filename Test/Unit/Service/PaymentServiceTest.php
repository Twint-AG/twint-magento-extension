<?php

declare(strict_types=1);

namespace Tests\Twint\Magento\Service;

use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Mockery;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Service\PaymentService;

/**
 * @internal
 */
class Test_Unit_PaymentServiceTest extends TestCase
{
    private $orderPaymentRepositoryMock;

    private $paymentService;

    protected function setUp(): void
    {
        $this->orderPaymentRepositoryMock = Mockery::mock(OrderPaymentRepositoryInterface::class);
        $this->paymentService = new PaymentService($this->orderPaymentRepositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testUpdate()
    {
        // Arrange
        $paymentMock = Mockery::mock(Payment::class);
        $transactionMock = Mockery::mock(Transaction::class);

        $transactionId = '123456';
        $transactionMock->shouldReceive('getId')
            ->once()
            ->andReturn($transactionId);

        $paymentMock->shouldReceive('setLastTransId')
            ->once()
            ->with($transactionId)
            ->andReturnSelf();

        $this->orderPaymentRepositoryMock->shouldReceive('save')
            ->once()
            ->with($paymentMock);

        // Act
        $this->paymentService->update($paymentMock, $transactionMock);

        // Assert
        $this->addToAssertionCount(1);
    }
}
