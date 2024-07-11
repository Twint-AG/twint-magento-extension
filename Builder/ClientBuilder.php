<?php

declare(strict_types=1);

namespace Twint\Magento\Builder;

use Soap\Engine\Transport;
use Throwable;
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
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Version;

class ClientBuilder
{
    private static array $instances = [];

    public function __construct(
        private readonly ConfigHelper $configHelper,
        private readonly CryptoHandler $cryptoService
    ) {
    }

    /**
     * @phpstan-type FutureVersionId = int<Version::NEXT,max>
     * @phpstan-type version = FutureVersionId
     */
    public function build(string|int $storeCode, int $version = Version::LATEST): InvocationRecordingClient
    {
        if (isset(self::$instances[$storeCode])) {
            return self::$instances[$storeCode];
        }

        $credentials = $this->configHelper->getConfigs()
            ->getCredentials();

        if ($credentials->getValidated() === false) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_NOT_VALIDATED);
        }

        $merchantId = $credentials->getMerchantId();
        $certificate = $credentials->getCertificate();
        $environment = new Environment($credentials->getEnvironment());
        if ($merchantId === '') {
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
                    MerchantId::fromString($merchantId),
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

            self::$instances[$storeCode] = $client;

            return $client;
        } catch (Throwable $e) {
            throw new InvalidConfigException(InvalidConfigException::ERROR_UNDEFINED, 0, $e);
        }
    }
}
