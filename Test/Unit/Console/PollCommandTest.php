<?php

declare(strict_types=1);

namespace Twint\Magento\Console\Command\Test;

use Exception;
use Magento\Framework\App\State;
use Magento\Framework\Logger\Monolog;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Console\Command\PollCommand;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\MonitorService;

/**
 * @internal
 */
class Test_Unit_Console_PollCommandTest extends TestCase
{
    private $stateMock;

    private $repositoryMock;

    private $loggerMock;

    private $monitorMock;

    private $inputMock;

    private $outputMock;

    private $pairingMock;

    private $command;

    protected function setUp(): void
    {
        $this->stateMock = Mockery::mock(State::class);
        $this->repositoryMock = Mockery::mock(PairingRepositoryInterface::class);
        $this->loggerMock = Mockery::mock(Monolog::class);
        $this->monitorMock = Mockery::mock(MonitorService::class);
        $this->inputMock = Mockery::mock(InputInterface::class);
        $this->outputMock = Mockery::mock(OutputInterface::class);
        $this->pairingMock = Mockery::mock(Pairing::class);

        $this->command = new PollCommand(
            $this->stateMock,
            $this->repositoryMock,
            $this->loggerMock,
            $this->monitorMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecutePairingNotFound()
    {
        $pairingId = '12345678-1234-5678-9876-123456789012';

        $this->outputMock
            ->shouldReceive('writeln')
            ->with('Running')
            ->once();

        $this->inputMock
            ->shouldReceive('getArgument')
            ->andReturn($pairingId);

        $this->repositoryMock
            ->shouldReceive('getByPairingId')
            ->andReturn(null);

        $this->loggerMock
            ->shouldReceive('info')
            ->with("TWINT pairing not exist: {$pairingId}");

        $this->outputMock
            ->shouldReceive('writeln')
            ->with('Pairing not exist')
            ->once();

        $result = $this->command->execute($this->inputMock, $this->outputMock);
        self::assertSame(1, $result);
    }

    public function testExecuteSuccessfulMonitor()
    {
        $pairingId = '12345678-1234-5678-9876-123456789012';

        $this->stateMock->shouldReceive('setAreaCode')
            ->andReturn(true);

        $this->inputMock
            ->shouldReceive('getArgument')
            ->with('pairing-id')
            ->andReturn($pairingId);

        $this->repositoryMock
            ->shouldReceive('getByPairingId')
            ->with($pairingId)
            ->andReturn($this->pairingMock);

        $this->pairingMock
            ->shouldReceive('getId')
            ->andReturn(1);

        $this->pairingMock
            ->shouldReceive('isExpress')
            ->andReturn(false);

        $this->pairingMock
            ->shouldReceive('isFinished')
            ->andReturn(false, true); // To break the loop after one iteration

        $this->pairingMock
            ->shouldReceive('getVersion')
            ->andReturn(1);

        $this->pairingMock
            ->shouldReceive('getCreatedAgo')
            ->andReturn(100);

        $this->repositoryMock
            ->shouldReceive('updateCheckedAt')
            ->with('1');

        $this->monitorMock
            ->shouldReceive('monitor')
            ->with($this->pairingMock);

        $this->outputMock
            ->shouldReceive('writeln')
            ->with('Running');

        $this->loggerMock
            ->shouldReceive('info')
            ->with("TWINT monitor: {$pairingId}: 1 100");

        $result = $this->command->execute($this->inputMock, $this->outputMock);
        self::assertSame(0, $result);
    }

    public function testExecuteThrowsException()
    {
        $pairingId = '12345678-1234-5678-9876-123456789012';

        $this->stateMock->shouldReceive('setAreaCode')
            ->andReturn(true);

        $this->inputMock
            ->shouldReceive('getArgument')
            ->with('pairing-id')
            ->andReturn($pairingId);

        $this->repositoryMock
            ->shouldReceive('getByPairingId')
            ->with($pairingId)
            ->andReturn($this->pairingMock);

        $this->pairingMock
            ->shouldReceive('isFinished')
            ->andReturn(false);

        $this->pairingMock
            ->shouldReceive('getVersion')
            ->andReturn(1);

        $this->repositoryMock
            ->shouldReceive('updateCheckedAt')
            ->with('1')
            ->andThrow(new Exception('Error'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once();

        $this->outputMock
            ->shouldReceive('writeln')
            ->with('Running');

        $result = $this->command->execute($this->inputMock, $this->outputMock);
        self::assertSame(1, $result);
    }
}
