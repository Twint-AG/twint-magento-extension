<?php

declare(strict_types=1);

namespace Twint\Magento\Setup;

use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $db = $setup->getConnection();

        $name = 'before_update_twint_pairing';

        $db->query("DROP TRIGGER IF EXISTS {$name};");

        $trigger = new Trigger();
        $trigger->setName($name);
        $trigger->setTable('twint_pairing');
        $trigger->setEvent('UPDATE');
        $trigger->setTime('BEFORE');
        $trigger->addStatement("
            DECLARE changed_columns INT;

            IF OLD.version <> NEW.version THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version conflict detected. Update aborted.';
            END IF;

            SET changed_columns = 0;
            
            IF NEW.quote_id <> OLD.quote_id OR (NEW.quote_id IS NULL XOR OLD.quote_id IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
           
            IF NEW.org_quote_id <> OLD.org_quote_id OR (NEW.org_quote_id IS NULL XOR OLD.org_quote_id IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.status <> OLD.status THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.token <> OLD.token OR (NEW.token IS NULL XOR OLD.token IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.store_id <> OLD.store_id OR (NEW.store_id IS NULL XOR OLD.store_id IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.shipping_id <> OLD.shipping_id OR (NEW.shipping_id IS NULL XOR OLD.shipping_id IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.order_id <> OLD.order_id OR (NEW.order_id IS NULL XOR OLD.order_id IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.customer <> OLD.customer OR (NEW.customer IS NULL XOR OLD.customer IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
          
            
            IF NEW.is_express <> OLD.is_express THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.amount <> OLD.amount THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.pairing_status <> OLD.pairing_status OR (NEW.pairing_status IS NULL XOR OLD.pairing_status IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;
            
            IF NEW.transaction_status <> OLD.transaction_status OR (NEW.transaction_status IS NULL XOR OLD.transaction_status IS NULL) THEN
                SET changed_columns = changed_columns + 1;
            END IF;                     
           
           IF changed_columns > 0 THEN
              SET NEW.version = OLD.version + 1;
           END IF;   
        ");

        $db->createTrigger($trigger);
    }
}
