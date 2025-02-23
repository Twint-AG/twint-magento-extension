<?php

declare(strict_types=1);

namespace Twint\Magento\Builder;

use Magento\Framework\App\ProductMetadataInterface;
use Soap\Engine\Transport;
use Throwable;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Exception\InvalidConfigException;
use Twint\Magento\Helper\ConfigHelper;
use Twint\Magento\Util\CryptoHandler;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Factory\DefaultSoapEngineFactory;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Soap\MessageRecorder;
use Twint\Sdk\InvocationRecorder\Soap\RecordingTransport;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\PlatformVersion;
use Twint\Sdk\Value\PluginVersion;
use Twint\Sdk\Value\ShopPlatform;
use Twint\Sdk\Value\ShopPluginInformation;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;

class ClientBuilder
{
    private static array $instances = [];

    public function __construct(
        private readonly ConfigHelper $configHelper,
        private readonly CryptoHandler $cryptoService,
        private readonly ProductMetadataInterface $system
    ) {
    }

    /**
     * @phpstan-type FutureVersionId = int<Version::NEXT,max>
     * @phpstan-type version = FutureVersionId
     */
    public function build(string|int $storeCode, int $version = Version::LATEST): InvocationRecordingClient
    {
        if (isset(self::$instances[$storeCode . $version])) {
            return self::$instances[$storeCode . $version];
        }

        $credentials = $this->configHelper->getConfigs()
            ->getCredentials();

        if ($credentials->getValidated() === false) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_NOT_VALIDATED);
        }

        $uuid = $credentials->getStoreUuid();
        $certificate = $credentials->getCertificate();
        $environment = new Environment($credentials->getEnvironment());
        if ($uuid === '') {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_MERCHANT_ID);
        }

        if ($certificate === []) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
        }

        try {
            $cert = $this->cryptoService->decrypt($certificate['certificate']);
            $passphrase = $this->cryptoService->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $cert === '') {
                throw new InvalidConfigException(InvalidConfigException::ERROR_INVALID_CERTIFICATE);
            }
            $messageRecorder = new MessageRecorder();

            $client = new InvocationRecordingClient(
                new Client(
                    CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                    new ShopPluginInformation(
                        StoreUuid::fromString($uuid),
                        ShopPlatform::MAGENTO(),
                        new PlatformVersion($this->system->getVersion()),
                        new PluginVersion(TwintConstant::MODULE_VERSION),
                        TwintConstant::installSource()
                    ),
                    // @phpstan-ignore-next-line
                    new Version($version),
                    $environment,
                    soapEngineFactory: new DefaultSoapEngineFactory(
                        wrapTransport: static fn (Transport $transport) => new RecordingTransport(
                            $transport,
                            $messageRecorder
                        )
                    )
                ),
                $messageRecorder
            );

            self::$instances[$storeCode . $version] = $client;

            return $client;
        } catch (Throwable $e) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_UNDEFINED, 0, $e);
        }
    }
}
