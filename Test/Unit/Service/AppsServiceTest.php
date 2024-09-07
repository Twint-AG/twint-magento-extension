<?php

declare(strict_types=1);

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Framework\App\CacheInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Service\AppsService;

class AppsServiceTest extends MockeryTestCase
{
    private $clientBuilderMock;
    private $cacheMock;
    private $appsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientBuilderMock = Mockery::mock(ClientBuilder::class);
        $this->cacheMock = Mockery::mock(CacheInterface::class);
        $this->appsService = new AppsService($this->clientBuilderMock, $this->cacheMock);
    }

    public function testBuildLinksForAndroid(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'Android User Agent';

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $clientMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $deviceMock = Mockery::mock('overload:Twint\Sdk\Value\DetectedDevice');

        $this->clientBuilderMock->shouldReceive('build')->with($storeCode)->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')->with($userAgent)->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')->andReturn(true);
        $deviceMock->shouldReceive('isIos')->andReturn(false);

        $expectedLinks = [
            'android' => 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code=' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end'
        ];

        $result = $this->appsService->buildLinks($storeCode, $token);

        $this->assertEquals($expectedLinks, $result);
    }

    public function testBuildLinksForIos(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'iOS User Agent';

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $clientMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $deviceMock = Mockery::mock('overload:Twint\Sdk\Value\DetectedDevice');
        $iosAppMock = Mockery::mock('overload:Twint\Sdk\Value\IosAppScheme');

        $this->clientBuilderMock->shouldReceive('build')->with($storeCode)->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')->with($userAgent)->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')->andReturn(false);
        $deviceMock->shouldReceive('isIos')->andReturn(true);

        $clientMock->shouldReceive('getIosAppSchemes')->andReturn([$iosAppMock]);
        $iosAppMock->shouldReceive('displayName')->andReturn('TWINT');
        $iosAppMock->shouldReceive('scheme')->andReturn('twint://');

        $expectedLinks = [
            'ios' => [
                [
                    'name' => 'TWINT',
                    'link' => 'twint://applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}'
                ]
            ]
        ];

        $result = $this->appsService->buildLinks($storeCode, $token);

        $this->assertEquals($expectedLinks, $result);
    }

    public function testGetLinksFromCache(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'Test User Agent';
        $cacheKey = $userAgent . $token . $storeCode;

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $cachedData = ['cached' => 'data'];
        $this->cacheMock->shouldReceive('load')->with($cacheKey)->andReturn(serialize($cachedData));

        $result = $this->appsService->getLinks($storeCode, $token);

        $this->assertEquals($cachedData, $result);
    }

    public function testGetLinksFromBuildAndCache(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'Test User Agent';
        $cacheKey = $userAgent . $token . $storeCode;

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $this->cacheMock->shouldReceive('load')->with($cacheKey)->andReturnNull();

        $clientMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $deviceMock = Mockery::mock('overload:Twint\Sdk\Value\DetectedDevice');

        $this->clientBuilderMock->shouldReceive('build')->with($storeCode)->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')->with($userAgent)->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')->andReturn(true);
        $deviceMock->shouldReceive('isIos')->andReturn(false);

        $expectedLinks = [
            'android' => 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code=' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end'
        ];

        $this->cacheMock->shouldReceive('save')->with(serialize($expectedLinks), $cacheKey, [], 86400)->once();

        $result = $this->appsService->getLinks($storeCode, $token);

        $this->assertEquals($expectedLinks, $result);
    }
}
