<?php

namespace Twint\Magento\Test\Unit\Service;

use Exception;
use Magento\Framework\Logger\Monolog;
use Mockery;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Api\RequestLogRepositoryInterface;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\RequestLog;
use Twint\Magento\Model\RequestLogFactory;
use Twint\Magento\Service\ApiService;

class ApiServiceTest extends TestCase
{
    private $apiService;
    private $factoryMock;
    private $repositoryMock;
    private $loggerMock;
    private $clientMock;
    private $invocationMock;
    private $requestLogMock;

    protected function setUp(): void
    {
        $this->factoryMock = $this->createMock(RequestLogFactory::class);
        $this->repositoryMock = $this->createMock(RequestLogRepositoryInterface::class);
        $this->loggerMock = $this->createMock(Monolog::class);
        $this->clientMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $this->invocationMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\Value\Invocation');
        $this->requestLogMock = $this->createMock(RequestLog::class);

        $this->apiService = new ApiService(
            $this->factoryMock,
            $this->repositoryMock,
            $this->loggerMock
        );
    }

    public function testCallSuccess()
    {
        $method = 'someMethod';
        $args = ['arg1', 'arg2'];
        $save = false;

        // Mock the API call success
        $this->clientMock->shouldReceive($method)
            ->andReturn('success');

        // Mock the flushInvocations method to return an array of invocations
        $this->clientMock->shouldReceive('flushInvocations')
            ->andReturn([$this->invocationMock]);

        // Mock the factory to return a request log
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->requestLogMock);

        $response = $this->apiService->call($this->clientMock, $method, $args, $save);

        // Assert the ApiResponse
        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertEquals('success', $response->getReturn());
        $this->assertEquals($this->requestLogMock, $response->getRequest());
    }

    public function testCallThrowsException()
    {
        $method = 'someMethod';
        $args = ['arg1', 'arg2'];

        // Mock the API call to throw an exception
        $this->clientMock->shouldReceive($method)
            ->andThrow(new Exception('API Error'));

        // Mock the flushInvocations method to return an array of invocations
        $this->clientMock->shouldReceive('flushInvocations')
            ->andReturn([$this->invocationMock]);

        // Mock the factory to return a request log
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->requestLogMock);

        $this->expectException(Exception::class);

        $res = $this->apiService->call($this->clientMock, $method, $args, false);
        $this->assertNull($res->getReturn());
    }

    public function testLogSuccess()
    {
        $method = 'someMethod';
        $save = true;

        // Mock the parsed result
        $this->invocationMock->shouldReceive('arguments')
            ->andReturn(['arg1', 'arg2']);
        $this->invocationMock->shouldReceive('returnValue')
            ->andReturn('success');
        $this->invocationMock->shouldReceive('exception')
            ->andReturn(null);
        $this->invocationMock->shouldReceive('messages')
            ->andReturn([]);

        // Mock the factory to create a log
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->requestLogMock);

        // Mock the repository save
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->requestLogMock)
            ->willReturn($this->requestLogMock);

        $log = $this->apiService->log($method, [$this->invocationMock], $save);

        // Assert log was returned
        $this->assertInstanceOf(RequestLog::class, $log);
    }
}
