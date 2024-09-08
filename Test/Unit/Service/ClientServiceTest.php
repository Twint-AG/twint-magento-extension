<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Magento\Framework\Logger\Monolog;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Mockery;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\ApiService;
use Twint\Magento\Service\ClientService;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Service\OrderService;
use Twint\Magento\Service\PairingService;

/**
 * @internal
 */
class Test_ClientServiceTest extends TestCase
{
    private $clientBuilder;

    private $pairingService;

    private $apiService;

    private $orderService;

    private $monitorService;

    private $logger;

    private $clientService;

    private $orderMock;

    protected function setUp(): void
    {
        $this->clientBuilder = Mockery::mock(ClientBuilder::class);
        $this->pairingService = Mockery::mock(PairingService::class);
        $this->apiService = Mockery::mock(ApiService::class);
        $this->orderService = Mockery::mock(OrderService::class);
        $this->monitorService = Mockery::mock(MonitorService::class);
        $this->logger = Mockery::mock(Monolog::class);

        $this->orderMock = Mockery::mock('overload:Twint\Sdk\Value\Order');

        $this->clientService = new ClientService(
            $this->clientBuilder,
            $this->pairingService,
            $this->apiService,
            $this->orderService,
            $this->monitorService,
            $this->logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testStartFastCheckoutOrder()
    {
        $order = Mockery::mock(Order::class);
        $payment = Mockery::mock(InfoInterface::class);
        $store = Mockery::mock(Store::class);
        $amount = 100.00;
        $pairing = Mockery::mock(Pairing::class);
        $client = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $apiResponse = Mockery::mock(ApiResponse::class);

        $payment->shouldReceive('getOrder')
            ->andReturn($order);
        $order->shouldReceive('getIncrementId')
            ->andReturn('000000001');
        $order->shouldReceive('getStore')
            ->andReturn($store);
        $store->shouldReceive('getId')
            ->andReturn(1);

        $pairing->shouldReceive('getStoreId')
            ->andReturn(1);
        $pairing->shouldReceive('getPairingId')
            ->andReturn('12345678-1234-5678-9876-123456789012');
        $pairing->shouldReceive('isFinished')
            ->andReturn(false, true);
        $pairing->shouldReceive('isSuccessful')
            ->andReturn(true);
        $pairing->shouldReceive('getStatus')
            ->andReturn('STATUS');
        $pairing->shouldReceive('getTransactionStatus')
            ->andReturn('T_STATUS');
        $pairing->shouldReceive('getPairingStatus')
            ->andReturn('P_STATUS');

        $this->clientBuilder->shouldReceive('build')
            ->with(1)
            ->andReturn($client);

        $this->apiService->shouldReceive('call')
            ->with($client, 'startFastCheckoutOrder', Mockery::type('array'))
            ->andReturn($apiResponse);

        $apiResponse->shouldReceive('getReturn')
            ->andReturn($this->orderMock);

        $this->pairingService->shouldReceive('create')
            ->with($amount, $apiResponse, $payment, true)
            ->andReturn([$pairing, 'history']);

        $this->monitorService->shouldReceive('monitor')
            ->with($pairing)
            ->andReturn($pairing);

        $this->logger->shouldReceive('info')
            ->with(Mockery::type('string'));

        $result = $this->clientService->startFastCheckoutOrder($payment, $amount, $pairing);

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertSame($this->orderMock, $result[0]);
        self::assertSame($pairing, $result[1]);
        self::assertSame('history', $result[2]);
    }

    public function testCreateOrder()
    {
        $order = Mockery::mock(Order::class);
        $payment = Mockery::mock(InfoInterface::class);
        $store = Mockery::mock(Store::class);
        $amount = 100.00;
        $client = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $apiResponse = Mockery::mock(ApiResponse::class);
        $twintOrder = $this->orderMock;
        $pairing = Mockery::mock(Pairing::class);

        $payment->shouldReceive('getOrder')
            ->andReturn($order);
        $order->shouldReceive('getIncrementId')
            ->andReturn('000000001');
        $order->shouldReceive('getStore')
            ->andReturn($store);
        $store->shouldReceive('getCode')
            ->andReturn('default');

        $this->clientBuilder->shouldReceive('build')
            ->andReturn($client);

        $this->apiService->shouldReceive('call')
            ->with($client, 'startOrder', Mockery::type('array'))
            ->andReturn($apiResponse);

        $apiResponse->shouldReceive('getReturn')
            ->andReturn($twintOrder);

        $this->pairingService->shouldReceive('create')
            ->with($amount, $apiResponse, $payment)
            ->andReturn([$pairing, 'history']);

        $this->orderService->shouldReceive('markAsPendingPayment')
            ->andReturn($order);

        $this->monitorService->shouldReceive('status')
            ->with($pairing);

        $result = $this->clientService->createOrder($payment, $amount);

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertSame($twintOrder, $result[0]);
        self::assertSame($pairing, $result[1]);
        self::assertSame('history', $result[2]);
    }

    public function testRefund()
    {
        $pairingId = '12345678-1234-5678-9876-123456789012';
        $reversalReference = 'reversal-ref';
        $amount = 50.00;
        $storeId = 1;
        $client = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $apiResponse = Mockery::mock(ApiResponse::class);

        $this->clientBuilder->shouldReceive('build')
            ->with($storeId)
            ->andReturn($client);

        $this->apiService->shouldReceive('call')
            ->with($client, 'reverseOrder', Mockery::type('array'))
            ->andReturn($apiResponse);

        $result = $this->clientService->refund($pairingId, $reversalReference, $amount, $storeId);

        self::assertInstanceOf(ApiResponse::class, $result);
    }
}
