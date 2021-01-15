<?php
namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $table = $installer->getTable('megaventory_inventories');
        
        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            if ($installer->getConnection()->tableColumnExists($table, 'stock_source_code') === false) {
                $installer->getConnection()->addColumn(
                    $table,
                    'stock_source_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 200,
                        'comment' => 'Magento Inventory Source'
                    ]
                );
            }

            if ($installer->getConnection()->tableColumnExists($table, 'isdefault') !== false) {
                $installer->getConnection()->dropColumn(
                    $table,
                    'isdefault'
                );
            }
        }

        $installer->endSetup();
    }
}
