<?php

declare(strict_types=1);

namespace Twint\Magento\Test\Unit\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Logger\Monolog;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\Monitor\MonitorStatus;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Service\Express\OrderConvertService;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Service\PairingService;

/**
 * @internal
 */
class MonitorServiceTest extends TestCase
{
    private $pairingRepositoryMock;

    private $pairingServiceMock;

    private $orderConvertServiceMock;

    private $directoryListMock;

    private $loggerMock;

    private $monitorService;

    protected function setUp(): void
    {
        $this->pairingRepositoryMock = Mockery::mock(PairingRepositoryInterface::class);
        $this->pairingServiceMock = Mockery::mock(PairingService::class);
        $this->orderConvertServiceMock = Mockery::mock(OrderConvertService::class);
        $this->directoryListMock = Mockery::mock(DirectoryList::class);
        $this->loggerMock = Mockery::mock(Monolog::class);

        $this->monitorService = new MonitorService(
            $this->pairingRepositoryMock,
            $this->pairingServiceMock,
            $this->orderConvertServiceMock,
            $this->directoryListMock,
            $this->loggerMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testMonitorWithFinishedPairing()
    {
        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('isFinished')
            ->andReturn(true);

        $result = $this->monitorService->monitor($pairingMock);

        self::assertSame($pairingMock, $result);
    }

    public function testMonitorWithExpressPairingAndPaidStatus()
    {
        $pairingId = '123';

        $historyMock = Mockery::mock(PairingHistory::class);

        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('isFinished')
            ->andReturn(false);
        $pairingMock->shouldReceive('isExpress')
            ->andReturn(true);
        $pairingMock->shouldReceive('getId')
            ->andReturn($pairingId);

        $statusMock = Mockery::mock(MonitorStatus::class);
        $statusMock->shouldReceive('paid')
            ->andReturn(true);
        $statusMock->shouldReceive('getAdditionalInformation')
            ->with('pairing')
            ->andReturn($pairingMock);
        $statusMock->shouldReceive('getAdditionalInformation')
            ->with('history')
            ->andReturn($historyMock);
        $statusMock->shouldReceive('setAdditionalInformation')
            ->andReturnUndefined();

        $this->pairingServiceMock->shouldReceive('monitorExpress')
            ->andReturn($statusMock);
        $this->pairingRepositoryMock->shouldReceive('markAsOrdering');
        $this->orderConvertServiceMock->shouldReceive('convert')
            ->andReturn('10000001');
        $this->pairingRepositoryMock->shouldReceive('markAsPaid');

        $result = $this->monitorService->monitor($pairingMock);

        self::assertInstanceOf(Pairing::class, $result);
    }

    public function testStatusWithFinishedPairing()
    {
        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('isFinished')
            ->andReturn(true);
        $pairingMock->shouldReceive('toMonitorStatus')
            ->andReturn(new MonitorStatus(true, 1));

        $result = $this->monitorService->status($pairingMock);

        self::assertInstanceOf(MonitorStatus::class, $result);
    }

    public function testStatusWithUnmonitoredPairing()
    {
        $pairingMock = Mockery::mock(Pairing::class);
        $pairingMock->shouldReceive('isFinished')
            ->andReturn(false);
        $pairingMock->shouldReceive('isMonitoring')
            ->andReturn(false);
        $pairingMock->shouldReceive('getPairingId')
            ->andReturn('123');
        $pairingMock->shouldReceive('getIsOrdering')
            ->andReturn(false);
        $pairingMock->shouldReceive('toMonitorStatus')
            ->andReturn(new MonitorStatus(false, -1));

        $this->directoryListMock->shouldReceive('getRoot')
            ->andReturn('/var/www/magento');

        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('setOptions');
        $processMock->shouldReceive('disableOutput');
        $processMock->shouldReceive('start');

        $result = $this->monitorService->status($pairingMock);

        self::assertInstanceOf(MonitorStatus::class, $result);
    }
}
