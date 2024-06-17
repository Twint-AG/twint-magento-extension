<?php

namespace Twint\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Twint\Core\Validator\CredentialValidator;
use Magento\Config\Model\Config\Factory;

class ConfigSaveObserver implements ObserverInterface
{

    public function __construct(private Factory                             $configFactory,
                                private \Magento\Config\Model\Config\Loader $configLoader,
                                private CredentialValidator                 $validator

    )
    {
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('configData') ?? [];

        if ($data['section'] == 'twint') {
            $fields = $data['groups']['credential']['fields'] ?? [];
            $merchantID = $fields['merchantID']['value'] ?? null;
            $env = $fields['environment']['value'] ?? null;
            $cert = json_decode($fields['certificate']['value'] ?? '', true);

            if (is_null($merchantID) || is_null($env) || is_null($cert))
                $validated = true;
            else
                $validated = $this->validator->validate($cert, $merchantID, (bool)$env);

            $data['groups']['credential']['fields']['validated'] = [
                'value' => $validated
            ];

            $configModel = $this->configFactory->create(['data' => $data]);
            $configModel->save();
        }
    }
}
