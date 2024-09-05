<?php

declare(strict_types=1);

namespace Twint\Magento\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Twint\Magento\Constant\TwintConstant;

class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $db = $setup->getConnection();

        $values = [
            // common
            TwintConstant::CONFIG_VALIDATED => 0,

            //regular
            'payment/twint_regular/sort_order' => 1,
            TwintConstant::REGULAR_ENABLED => 0,

            // express
            'payment/twint_express/sort_order' => 2,
            TwintConstant::EXPRESS_ENABLED => 0,
        ];

        $records = [];
        foreach ($values as $key => $value) {
            $records[] = [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => $key,
                'value' => (string)$value
            ];
        }

        $db->insertMultiple('core_config_data', $records);
    }
}
