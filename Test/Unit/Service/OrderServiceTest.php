<?php

declare(strict_types=1);

namespace Twint\Magento\Tests\Service;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\Search\SearchResult;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Mockery;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\OrderService;

/**
 * @internal
 */
class Test_Unit_OrderServiceTest extends TestCase
{
    private $checkoutSession;

    private $orderRepository;

    private $paymentRepository;

    private $criteriaBuilder;

    private $priceCurrency;

    private $orderService;

    protected function setUp(): void
    {
        $this->checkoutSession = Mockery::mock(Session::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->paymentRepository = Mockery::mock(OrderPaymentRepositoryInterface::class);
        $this->criteriaBuilder = Mockery::mock(SearchCriteriaBuilder::class);
        $this->priceCurrency = Mockery::mock(PriceCurrencyInterface::class);

        $this->orderService = new OrderService(
            $this->checkoutSession,
            $this->orderRepository,
            $this->paymentRepository,
            $this->criteriaBuilder,
            $this->priceCurrency
        );
    }

    public function testPay()
    {
        $pairing = Mockery::mock(Pairing::class);
        $transaction = Mockery::mock(Transaction::class);
        $order = Mockery::mock(Order::class);
        $payment = Mockery::mock(Payment::class);

        // Mock Pairing and Transaction
        $pairing->shouldReceive('getOrderId')
            ->andReturn('000000001');
        $pairing->shouldReceive('getAmount')
            ->andReturn(100.00);
        $transaction->shouldReceive('getTxnId')
            ->andReturn('txn123');

        $resultMock = Mockery::mock(SearchResult::class);
        $resultMock->shouldReceive('getItems')
            ->andReturn([$order]);

        // Mock Order retrieval
        $this->orderRepository
            ->shouldReceive('getList')
            ->andReturn($resultMock);

        // Mock Price Currency
        $this->priceCurrency->shouldReceive('round')
            ->with(100.00)
            ->andReturn(100.00);

        // Mock Order methods
        $order->shouldReceive('getBaseToOrderRate')
            ->andReturn(1);
        $order->shouldReceive('getShippingAmount')
            ->andReturn(1);
        $order->shouldReceive('getBaseShippingAmount')
            ->andReturn(1);
        $order->shouldReceive('getStatus')
            ->andReturn('Processing');
        $order->shouldReceive('setTotalPaid')
            ->with(100.00)
            ->once();
        $order->shouldReceive('setBaseTotalPaid')
            ->with(100.00)
            ->once();
        $order->shouldReceive('setTotalDue')
            ->with(0)
            ->once();
        $order->shouldReceive('setBaseTotalDue')
            ->with(0)
            ->once();
        $order->shouldReceive('addCommentToStatusHistory')
            ->once();
        $this->orderRepository->shouldReceive('save')
            ->with($order)
            ->once();

        // Mock Payment methods
        $order->shouldReceive('getPayment')
            ->andReturn($payment);
        $payment->shouldReceive('setAmountPaid')
            ->with(100.00)
            ->once();
        $payment->shouldReceive('setBaseAmountPaid')
            ->with(100.00)
            ->once();
        $payment->shouldReceive('setShippingCaptured')
            ->once();
        $payment->shouldReceive('setBaseShippingCaptured')
            ->once();
        $payment->shouldReceive('setTransactionId')
            ->with('txn123')
            ->once();
        $payment->shouldReceive('setLastTransId')
            ->with('txn123')
            ->once();
        $this->paymentRepository->shouldReceive('save')
            ->with($payment)
            ->once();

        $criteria = Mockery::mock(SearchCriteria::class);
        $this->criteriaBuilder->shouldReceive('addFilter')
            ->andReturn($this->criteriaBuilder);
        $this->criteriaBuilder->shouldReceive('create')
            ->andReturn($criteria);

        // Call the method under test
        $result = $this->orderService->pay($pairing, $transaction);

        // Assert that the result is the expected order object
        self::assertInstanceOf(Order::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
