<?php

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Magento\Service\InvoiceService;
use Mockery;

class InvoiceServiceTest extends TestCase
{
    private $invoiceRepositoryMock;
    private $scopeConfigMock;
    private $invoiceSenderMock;
    private $loggerMock;
    private $invoiceService;

    protected function setUp(): void
    {
        $this->invoiceRepositoryMock = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->scopeConfigMock = Mockery::mock(ScopeConfigInterface::class);
        $this->invoiceSenderMock = Mockery::mock(InvoiceSender::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->invoiceService = new InvoiceService(
            $this->invoiceRepositoryMock,
            $this->scopeConfigMock,
            $this->invoiceSenderMock,
            $this->loggerMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreateInvoiceSuccess()
    {
        $orderMock = Mockery::mock(Order::class);
        $transactionMock = Mockery::mock(Transaction::class);
        $invoiceMock = Mockery::mock(Invoice::class);

        $orderMock->shouldReceive('canInvoice')->andReturn(true);
        $orderMock->shouldReceive('prepareInvoice')->andReturn($invoiceMock);
        $orderMock->shouldReceive('getStoreId')->andReturn(1);

        $invoiceMock->shouldReceive('getOrder->setIsInProcess')->with(true)->andReturnSelf();
        $invoiceMock->shouldReceive('setTransactionId')->andReturnSelf();
        $invoiceMock->shouldReceive('register')->andReturnSelf();
        $invoiceMock->shouldReceive('pay')->andReturnSelf();

        $transactionMock->shouldReceive('getTxnId')->andReturn('transaction123');

        $this->invoiceRepositoryMock->shouldReceive('save')->with($invoiceMock);

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(InvoiceIdentity::XML_PATH_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE, 1)
            ->andReturn(false);

        $result = $this->invoiceService->create($orderMock, $transactionMock);

        $this->assertSame($invoiceMock, $result);
    }

    public function testCreateInvoiceCannotInvoice()
    {
        $orderMock = Mockery::mock(Order::class);
        $transactionMock = Mockery::mock(Transaction::class);

        $orderMock->shouldReceive('canInvoice')->andReturn(false);

        $result = $this->invoiceService->create($orderMock, $transactionMock);

        $this->assertNull($result);
    }

    public function testCreateInvoiceWithException()
    {
        $orderMock = Mockery::mock(Order::class);
        $transactionMock = Mockery::mock(Transaction::class);

        $orderMock->shouldReceive('canInvoice')->andReturn(true);
        $orderMock->shouldReceive('prepareInvoice')->andThrow(new LocalizedException(__('Test exception')));

        $this->loggerMock->shouldReceive('error')->with(Mockery::pattern('/Cannot create invoice/'));

        $this->expectException(LocalizedException::class);

        $this->invoiceService->create($orderMock, $transactionMock);
    }

    public function testSendInvoiceMailSuccess()
    {
        $invoiceMock = Mockery::mock(Invoice::class);

        $this->invoiceSenderMock->shouldReceive('send')->with($invoiceMock);

        $this->invoiceService->sendInvoiceMail($invoiceMock);

        // If we reach here without any exception, the test is successful
        $this->assertTrue(true);
    }

    public function testSendInvoiceMailWithException()
    {
        $invoiceMock = Mockery::mock(Invoice::class);

        $this->invoiceSenderMock->shouldReceive('send')->with($invoiceMock)->andThrow(new \Exception('Test exception'));

        $this->loggerMock->shouldReceive('error')->with(Mockery::pattern('/Exception in Send Mail in Magento/'));

        $this->invoiceService->sendInvoiceMail($invoiceMock);

        // If we reach here without any exception, the test is successful
        $this->assertTrue(true);
    }
}
