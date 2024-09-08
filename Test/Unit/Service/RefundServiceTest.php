<?php

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Api\RefundRepositoryInterface;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\Refund;
use Twint\Magento\Model\RefundFactory;
use Twint\Magento\Service\ClientService;
use Twint\Magento\Service\PairingService;
use Twint\Magento\Service\RefundService;

class RefundServiceTest extends TestCase
{
    private MockInterface $clientServiceMock;
    private MockInterface $pairingRepositoryMock;
    private MockInterface $refundRepositoryMock;
    private MockInterface $refundFactoryMock;
    private MockInterface $sessionMock;
    private MockInterface $pairingServiceMock;
    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientServiceMock = Mockery::mock(ClientService::class);
        $this->pairingRepositoryMock = Mockery::mock(PairingRepositoryInterface::class);
        $this->refundRepositoryMock = Mockery::mock(RefundRepositoryInterface::class);
        $this->refundFactoryMock = Mockery::mock(RefundFactory::class);
        $this->sessionMock = Mockery::mock(Session::class);
        $this->pairingServiceMock = Mockery::mock(PairingService::class);

        $this->refundService = new RefundService(
            $this->clientServiceMock,
            $this->pairingRepositoryMock,
            $this->refundRepositoryMock,
            $this->refundFactoryMock,
            $this->sessionMock,
            $this->pairingServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRefundWithValidPairing(): void
    {
        $pairingId = 1;
        $amount = 100.00;
        $reversalReference = 'R-123-' . time();

        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('getPairingId')->andReturn('twint_pairing_id');
        $pairingMock->shouldReceive('getOrderId')->andReturn('123');
        $pairingMock->shouldReceive('getStoreId')->andReturn('1');
        $pairingMock->shouldReceive('getId')->andReturn('1');

        $this->pairingRepositoryMock->shouldReceive('getById')
            ->with($pairingId)
            ->andReturn($pairingMock);

        $apiResponseMock = Mockery::mock(ApiResponse::class);
        $orderMock = Mockery::mock('overload:Twint\Sdk\Value\Order');
        $orderMock->shouldReceive('status')->andReturn('COMPLETED');

        $apiResponseMock->shouldReceive('getReturn')->andReturn($orderMock);
        $apiResponseMock->shouldReceive('getRequest->getId')->andReturn('request_id');

        $this->clientServiceMock->shouldReceive('refund')
            ->with('twint_pairing_id', $reversalReference, $amount, 1)
            ->andReturn($apiResponseMock);

        $refundMock = Mockery::mock(Refund::class);
        $this->refundFactoryMock->shouldReceive('create')->andReturn($refundMock);

        $refundMock->shouldReceive('setData')->withAnyArgs()->andReturnSelf();

        $this->refundRepositoryMock->shouldReceive('save')
            ->with($refundMock)
            ->andReturn($refundMock);

        $this->pairingServiceMock->shouldReceive('createHistory')
            ->with($pairingMock, Mockery::any(), $amount)
            ->once();

        $this->sessionMock->shouldReceive('getUser')->andReturn(null);

        $result = $this->refundService->refund($pairingId, $amount, $reversalReference);

        $this->assertInstanceOf(Refund::class, $result);
    }

    public function testGetRefundableAmount(): void
    {
        $pairingId = 1;
        $pairingAmount = 200.00;
        $totalRefunded = 50.00;

        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('getId')->andReturn($pairingId);
        $pairingMock->shouldReceive('getAmount')->andReturn($pairingAmount);

        $this->pairingRepositoryMock->shouldReceive('getById')
            ->with($pairingId)
            ->andReturn($pairingMock);

        $this->refundRepositoryMock->shouldReceive('getTotalRefundedAmount')
            ->with($pairingId)
            ->andReturn($totalRefunded);

        $result = $this->refundService->getRefundableAmount($pairingId);

        $this->assertEquals(150.00, $result);
    }

    public function testValidateThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Amount to refund should be greater than 0');

        $pairingMock = Mockery::mock(Pairing::class);
        $amount = -10.00;

        $method = new \ReflectionMethod(RefundService::class, 'validate');
        $method->setAccessible(true);
        $method->invoke($this->refundService, $pairingMock, $amount);
    }
}
