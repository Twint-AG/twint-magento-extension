<?php

declare(strict_types=1);

namespace Twint\Magento\Validator;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Logger\Monolog;
use Throwable;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Util\CryptoHandler;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\PlatformVersion;
use Twint\Sdk\Value\PluginVersion;
use Twint\Sdk\Value\ShopPlatform;
use Twint\Sdk\Value\ShopPluginInformation;
use Twint\Sdk\Value\StoreUuid;
use Twint\Sdk\Value\Version;

class CredentialValidator
{
    public function __construct(
        readonly CryptoHandler $crypto,
        private readonly ProductMetadataInterface $system,
        private readonly Monolog $logger,
    ) {
    }

    public function validate(array $certificate, string $storeUuid, string $environment): bool
    {
        try {
            $cert = $this->crypto->decrypt($certificate['certificate']);
            $passphrase = $this->crypto->decrypt($certificate['passphrase']);

            if ($passphrase === '' || $cert === '') {
                return false;
            }

            $client = new Client(
                CertificateContainer::fromPkcs12(new Pkcs12Certificate(new InMemoryStream($cert), $passphrase)),
                new ShopPluginInformation(
                    StoreUuid::fromString($storeUuid),
                    ShopPlatform::MAGENTO(),
                    new PlatformVersion($this->system->getVersion()),
                    new PluginVersion(TwintConstant::MODULE_VERSION),
                    TwintConstant::installSource()
                ),
                Version::latest(),
                new Environment($environment),
            );
            $status = $client->checkSystemStatus();
        } catch (Throwable $e) {
            $this->logger->error($this->buildLogMessage($e));
            return false;
        }

        return $status->isOk();
    }

    private function buildLogMessage(Throwable $e, string $message = ''): string
    {
        // Set a default message if none is provided
        if ($message === '' || $message === '0') {
            $message = 'TWINT verify certificate error: ' . $e->getMessage();
        }

        // Append details about previous exceptions recursively
        $previous = $e->getPrevious();
        if ($previous instanceof Throwable) {
            $message .= sprintf(
                "\n %s:%d %s -> %s",
                $previous->getFile(),
                $previous->getLine(),
                get_class($previous),
                $this->buildLogMessage($previous)
            );
        }

        return $message;
    }
}
