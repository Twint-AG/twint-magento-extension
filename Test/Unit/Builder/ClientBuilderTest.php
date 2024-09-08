<?php

namespace Tests\Twint\Magento\Builder;

use PHPUnit\Framework\TestCase;
use Mockery;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Helper\ConfigHelper;
use Twint\Magento\Util\CryptoHandler;
use Twint\Magento\Exception\InvalidConfigException;
use Twint\Sdk\Value\Version;

class ClientBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBuildWithValidConfig()
    {
        $client = Mockery::mock("overload:Twint\Sdk\InvocationRecorder\InvocationRecordingClient");
        $configHelper = Mockery::mock(ConfigHelper::class);
        $cryptoHandler = Mockery::mock(CryptoHandler::class);

        $configs = Mockery::mock();
        $credentials = Mockery::mock();
        $credentials->shouldReceive('getEnvironment')->andReturn('TESTING');
        $credentials->shouldReceive('getStoreUuid')->andReturn('12345678-1234-5678-9876-123456789012');

        $configHelper->shouldReceive('getConfigs')->andReturn($configs);
        $configs->shouldReceive('getCredentials')->andReturn($credentials);

        $credentials->shouldReceive('getValidated')->andReturn(true);
        $credentials->shouldReceive('getStoreUuid')->andReturn('test-uuid');
        $credentials->shouldReceive('getCertificate')->andReturn([
            'certificate' => 'encrypted-cert',
            'passphrase' => 'encrypted-passphrase'
        ]);
        $credentials->shouldReceive('getEnvironment')->andReturn('test');

        $cryptoHandler->shouldReceive('decrypt')
            ->with('encrypted-cert')
            ->andReturn('decrypted-cert');
        $cryptoHandler->shouldReceive('decrypt')
            ->with('encrypted-passphrase')
            ->andReturn('decrypted-passphrase');

        $clientBuilder = new ClientBuilder($configHelper, $cryptoHandler);

        $result = $clientBuilder->build('store1', Version::LATEST);

        $this->assertInstanceOf('Twint\Sdk\InvocationRecorder\InvocationRecordingClient', $result);
    }

    public function testBuildWithInvalidConfig()
    {
        $configHelper = Mockery::mock(ConfigHelper::class);
        $cryptoHandler = Mockery::mock(CryptoHandler::class);

        $configs = Mockery::mock();
        $credentials = Mockery::mock();

        $configHelper->shouldReceive('getConfigs')->andReturn($configs);
        $configs->shouldReceive('getCredentials')->andReturn($credentials);

        $credentials->shouldReceive('getValidated')->andReturn(false);

        $clientBuilder = new ClientBuilder($configHelper, $cryptoHandler);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(InvalidConfigException::ERROR_NOT_VALIDATED);

        $clientBuilder->build('store1', Version::NEXT);
    }
}
