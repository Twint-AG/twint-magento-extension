<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Logger\Monolog;
use Throwable;
use Twint\Magento\Api\RequestLogRepositoryInterface;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\RequestLog;
use Twint\Magento\Model\RequestLogFactory;
use Twint\Sdk\Exception\ApiFailure;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Value\Invocation;

class ApiService
{
    public function __construct(
        private readonly RequestLogFactory $factory,
        private readonly RequestLogRepositoryInterface $repository,
        private readonly Monolog $logger
    ) {
    }

    /**
     * @throws Throwable
     */
    public function call(InvocationRecordingClient $client, string $method, array $args, bool $save = true): ApiResponse
    {
        try {
            $returnValue = $client->{$method}(...$args);
        } catch (Throwable $e) {
            $this->logger->error("TWINT {$method} cannot handle success" . $e->getMessage());
            throw $e;
        } finally {
            $invocations = $client->flushInvocations();
            $log = $this->log($method, $invocations, $save);
        }

        return new ApiResponse($returnValue ?? null, $log);
    }

    /**
     * @param Invocation[] $invocation
     */
    public function log(string $method, array $invocation, bool $save): RequestLog
    {
        $log = $this->factory->create();

        try {
            list($request, $response, $soapRequests, $soapResponses, $soapActions, $exception) = $this->parse(
                $invocation
            );

            $log->setData('method', $method);
            $log->setData('request', $request);
            $log->setData('response', $response);
            $log->setData('soap_action', json_encode($soapActions));
            $log->setData('soap_request', json_encode($soapRequests));
            $log->setData('soap_response', json_encode($soapResponses));
            $log->setData('exception', $exception ?? null);

            // write log if get exception immediately
            if (!$exception && !$save) {
                return $log;
            }

            return $this->repository->save($log);
        } catch (Throwable $e) {
            $this->logger->error('TWINT cannot save request log: ' . $e->getMessage());
        }

        return $log;
    }

    /**
     * @param Invocation[] $invocations
     */
    protected function parse(array $invocations): array
    {
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? null;
        if ($exception instanceof ApiFailure) {
            $exception = $exception->getMessage();
        }
        $response = json_encode($invocations[0]->returnValue());
        $soapMessages = $invocations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        $soapActions = [];
        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()->body();
            $soapActions[] = $soapMessage->request()->action();
        }

        return [$request, $response, $soapRequests, $soapResponses, $soapActions, $exception];
    }

    /**
     * Utility method to save log
     */
    public function saveLog(RequestLog $log): mixed
    {
        return $this->repository->save($log);
    }
}
