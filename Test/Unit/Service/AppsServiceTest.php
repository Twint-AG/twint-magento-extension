<?php

declare(strict_types=1);

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Framework\App\CacheInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Service\AppsService;

/**
 * @internal
 */
class Test_Unit_AppsServiceTest extends MockeryTestCase
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
        $urlMock = Mockery::mock('overload:Twint\Sdk\Value\Url');

        $this->clientBuilderMock->shouldReceive('build')
            ->with($storeCode)
            ->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')
            ->with($userAgent)
            ->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')
            ->andReturn(true);
        $deviceMock->shouldReceive('isIos')
            ->andReturn(false);

        $clientMock->shouldReceive('getAndroidAppUrl')
            ->andReturn($urlMock);
        $urlMock->shouldReceive('__toString')
            ->andReturn('android-url');

        $expectedLinks = [
            'android' => 'android-url',
        ];

        $result = $this->appsService->getLinks($storeCode, $token);

        self::assertSame($expectedLinks, $result);
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
        $urlMock = Mockery::mock('overload:Twint\Sdk\Value\Url');

        $this->clientBuilderMock->shouldReceive('build')
            ->with($storeCode)
            ->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')
            ->with($userAgent)
            ->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')
            ->andReturn(false);
        $deviceMock->shouldReceive('isIos')
            ->andReturn(true);

        $clientMock->shouldReceive('getIosAppSchemes')
            ->andReturn([$iosAppMock]);
        $iosAppMock->shouldReceive('displayName')
            ->andReturn('TWINT');

        $clientMock->shouldReceive('getIosAppUrl')
            ->with($iosAppMock, Mockery::any())
            ->andReturn($urlMock);
        $urlMock->shouldReceive('__toString')
            ->andReturn('ios-url');

        $expectedLinks = [
            'ios' => [
                [
                    'name' => 'TWINT',
                    'link' => 'ios-url',
                ],
            ],
        ];

        $result = $this->appsService->getLinks($storeCode, $token);

        self::assertSame($expectedLinks, $result);
    }

    public function testGetLinksFromCache(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'Test User Agent';
        $cacheKey = $userAgent . $token . $storeCode;

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $cachedData = [
            'cached' => 'data',
        ];
        $this->cacheMock->shouldReceive('load')
            ->with($cacheKey)
            ->andReturn(serialize($cachedData));

        $result = $this->appsService->getCachedLinks($storeCode, $token);

        self::assertSame($cachedData, $result);
    }

    public function testGetLinksFromBuildAndCache(): void
    {
        $storeCode = 'default';
        $token = 'test-token';
        $userAgent = 'Test User Agent';
        $cacheKey = $userAgent . $token . $storeCode;

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $this->cacheMock->shouldReceive('load')
            ->with($cacheKey)
            ->andReturnNull();

        $clientMock = Mockery::mock('overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient');
        $deviceMock = Mockery::mock('overload:Twint\Sdk\Value\DetectedDevice');
        $urlMock = Mockery::mock('overload:Twint\Sdk\Value\Url');

        $this->clientBuilderMock->shouldReceive('build')
            ->with($storeCode)
            ->andReturn($clientMock);
        $clientMock->shouldReceive('detectDevice')
            ->with($userAgent)
            ->andReturn($deviceMock);
        $deviceMock->shouldReceive('isAndroid')
            ->andReturn(true);
        $deviceMock->shouldReceive('isIos')
            ->andReturn(false);

        $clientMock->shouldReceive('getAndroidAppUrl')
            ->andReturn($urlMock);
        $urlMock->shouldReceive('__toString')
            ->andReturn('android-url');

        $expectedLinks = [
            'android' => 'android-url',
        ];

        $this->cacheMock->shouldReceive('save')
            ->with(serialize($expectedLinks), $cacheKey, [], 86400)->once();

        $result = $this->appsService->getCachedLinks($storeCode, $token);

        self::assertSame($expectedLinks, $result);
    }
}
