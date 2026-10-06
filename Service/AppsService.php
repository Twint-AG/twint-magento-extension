<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Exception;
use Magento\Framework\App\CacheInterface;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Sdk\Value\AlphanumericPairingToken;
use function Psl\Type\string;

class AppsService
{
    private const CACHE_DURATION = 86400; // 1 hour

    public function __construct(
        private readonly ClientBuilder $connector,
        private readonly CacheInterface $cache
    ) {
    }

    public function getLinks(string $storeCode, string $token = '--TOKEN--'): array
    {
        $links = [];

        try {
            $client = $this->connector->build($storeCode);
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));
            $pairingToken = AlphanumericPairingToken::fromString($token);

            if ($device->isAndroid()) {
                $links['android'] = (string) $client->getAndroidAppUrl($pairingToken);
            } elseif ($device->isIos()) {
                $links['ios'] = [];

                foreach ($client->getIosAppSchemes() as $app) {
                    $links['ios'][] = [
                        'name' => $app->displayName(),
                        'link' => (string) $client->getIosAppUrl($app, $pairingToken),
                    ];
                }
            }
        } catch (Exception $e) {
            return $links;
        }

        return $links;
    }

    public function getCachedLinks(string $storeCode, string $token = '--TOKEN--'): array
    {
        $key = ($_SERVER['HTTP_USER_AGENT'] ?? '') . $token . $storeCode;

        $data = $this->cache->load($key);
        if ($data) {
            return unserialize($data);
        }

        $data = $this->getLinks($storeCode, $token);
        $this->cache->save(serialize($data), $key, [], self::CACHE_DURATION);

        return $data;
    }
}
