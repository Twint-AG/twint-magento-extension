<?php

declare(strict_types=1);

namespace Twint\Magento\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InsertDefaultConfig implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private ConfigResource $configResource
    ) {
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()
            ->startSetup();

        $defaultConfig = [
            // common
            'twint/credentials/validated' => 0,

            //regular
            'payment/twint_regular/sort_order' => 1,
            'twint/regular/enabled' => 0,

            // express
            'payment/twint_express/sort_order' => 2,
            'twint/express/enabled' => 0,
        ];

        foreach ($defaultConfig as $path => $value) {
            $this->configResource->saveConfig($path, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }

        $this->moduleDataSetup->getConnection()
            ->endSetup();
    }
}
