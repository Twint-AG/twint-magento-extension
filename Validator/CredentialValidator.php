<?php

declare(strict_types=1);

namespace Twint\Magento\Validator;

use Magento\Framework\App\ProductMetadataInterface;
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
        private readonly ProductMetadataInterface $system
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
            return false;
        }

        return $status->isOk();
    }
}
