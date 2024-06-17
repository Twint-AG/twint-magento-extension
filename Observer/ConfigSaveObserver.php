<?php

namespace Twint\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Twint\Core\Constant\TwintConstant;
use Twint\Core\Validator\CredentialValidator;
use Magento\Config\Model\Config\Factory;
use Twint\Core\Helper\ConfigHelper;

class ConfigSaveObserver implements ObserverInterface
{

    public function __construct(private Factory             $configFactory,
                                private ConfigHelper        $configHelper,
                                private CredentialValidator $validator

    )
    {
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('configData') ?? [];

        if ($data['section'] == TwintConstant::SECTION) {
            try {
                $credentials = $this->getCurrentCredentials($data);
                $validated = $this->validator->validate($credentials['certificate'], $credentials['merchant_id'], (bool)$credentials['test_mode']);

                $data['groups']['credentials']['fields']['validated'] = [
                    'value' => (int) $validated,
                ];

                $configModel = $this->configFactory->create(['data' => $data]);
                $configModel->save();
            }catch (\Throwable $e){
                dd($e);
            }
        }
    }

    private function getCurrentCredentials(array $data)
    {
        $scope = $this->getScope($data);
        $credentials = [];

        switch ($scope) {
            case 'default':
                $fields = $data['groups']['credentials']['fields'] ?? [];
                $credentials = [
                    'merchant_id' => $fields['merchantID']['value'] ?? null,
                    'test_mode' => $fields['environment']['value'] ?? null,
                    'certificate' => json_decode($fields['certificate']['value'] ?? '', true)
                ];
                break;

            case 'websites':
            case 'stores':
                $credentials = $this->configHelper->getCredentials($scope);
                $credentials['certificate'] = json_decode($credentials['certificate'], true);
                break;
        }

        return $credentials;
    }

    private function getScope(array $data)
    {
        $scope = 'default';
        if ($data['website']) {
            $scope = 'websites';
        }

        if ($data['store']) {
            $scope = 'stores';
        }

        return $scope;
    }
}
