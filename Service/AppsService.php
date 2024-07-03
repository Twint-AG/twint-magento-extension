<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Exception;
use Twint\Magento\Builder\ClientBuilder;
use function Psl\Type\string;

class AppsService
{
    public function __construct(
        private readonly ClientBuilder $connector,
    ) {
    }

    public function getLinks(string $storeCode, string $token = '--TOKEN--')
    {
        $links = [];

        try {
            $client = $this->connector->build($storeCode);
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));

            if ($device->isAndroid()) {
                $links['android'] = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code =' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end';
            } elseif ($device->isIos()) {
                $links['ios'] = [];

                foreach ($client->getIosAppSchemes() as $app) {
                    $links['ios'][] = [
                        'name' => $app->displayName(),
                        'link' => $app->scheme() . 'applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}',
                    ];
                }
            }
        } catch (Exception $e) {
            return $links;
        }

        return $links;
    }
}
