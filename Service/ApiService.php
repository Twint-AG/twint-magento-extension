<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

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
        private readonly RequestLogFactory             $factory,
        private readonly RequestLogRepositoryInterface $repository,
    )
    {
    }

    public function call(InvocationRecordingClient $client, string $method, array $args, bool $save = true): ApiResponse
    {
        try {
            $returnValue = $client->{$method}(...$args);
        } finally {
            $invocations = $client->flushInvocations();
            $log = $this->log($method, $invocations, $save);
        }

        return new ApiResponse($returnValue ?? null, $log);
    }

    /**
     * @param Invocation[] $invocation
     */
    protected function log(string $method, array $invocation, bool $save = true): RequestLog
    {
        $log = $this->factory->create();

        try {
            list($request, $response, $soapRequests, $soapResponses, $soapActions, $exception) = $this->parse(
                $invocation
            );

            /** @var RequestLog $log */
            $log->setData('method', $method);
            $log->setData('request', $request);
            $log->setData('response', $response);
            $log->setData('soap_action', json_encode($soapActions));
            $log->setData('soap_request', json_encode($soapRequests));
            $log->setData('soap_response', json_encode($soapResponses));
            $log->setData('exception', $exception ?? null);

            if (!$save) {
                return $log;
            }

            return $this->repository->save($log);
        } catch (Throwable $e) {
        }

        return $log;
    }

    /**
     * @param Invocation[] $invocations
     */
    protected function parse(array $invocations): array
    {
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? ' ';
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
