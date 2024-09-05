<?php
namespace Twint\Magento\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface{

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $db = $setup->getConnection();

        $db->query("DELETE FROM core_config_data WHERE `path` LIKE 'twint\/%' OR `path` LIKE 'payment\/twint%';");
    }
}
