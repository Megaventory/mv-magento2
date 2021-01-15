<?php
/**
 * Copyright © 2016 Megaventory. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'newsletter_subscriber'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('megaventory_log'))
            ->addColumn(
                'log_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Log Id'
            )
            ->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                500,
                ['nullable' => true, 'default' => null],
                'Error Code'
            )
            ->addColumn(
                'result',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true, 'default' => null],
                'Result'
            )
            ->addColumn(
                'magento_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => true, 'default' => null],
                'Magento Id'
            )
            ->addColumn(
                'details',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true, 'default' => null],
                'Details'
            )
            ->addColumn(
                'return_entity',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true, 'default' => null],
                'Return Entity'
            )
            ->addColumn(
                'data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true, 'default' => null],
                'Extra Data'
            )
            ->addColumn(
                'timestamp',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Added At'
            );
        
        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_inventories'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'shortname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => true, 'default' => null],
            'Short Name'
        )
        ->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            500,
            ['nullable' => true, 'default' => null],
            'Name'
        )
        ->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true, 'default' => null],
            'Address'
        )
        ->addColumn(
            'megaventory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Megaventory Id'
        )
        ->addColumn(
            'stock_source_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            200,
            ['nullable' => true, 'default' => null],
            'Magento Inventory Source'
        )
        ->addColumn(
            'counts_in_total_stock',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['default' => 1],
            'Counts In Total Stock'
        );
        
        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_stock'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Product Id'
        )
        ->addColumn(
            'parent_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Parent Id'
        )
        ->addColumn(
            'inventory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Inventory Id'
        )
        ->addColumn(
            'stockqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Qty'
        )
        ->addColumn(
            'stockqtyonhold',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Qty On Hold'
        )
        ->addColumn(
            'stockalarmqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Alarm Qty'
        )
        ->addColumn(
            'stocknonshippedqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Non Shipped Qty'
        )
        ->addColumn(
            'stocknonreceivedqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Non Received Qty'
        )
        ->addColumn(
            'stockwipcomponentqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Wip Qty'
        )
        ->addColumn(
            'stocknonreceivedwoqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Non Received Work Order Qty'
        )
        ->addColumn(
            'stocknonallocatedwoqty',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '18,9',
            ['default' => 0],
            'Non Allocated Work Order Qty'
        )
        ->addColumn(
            'extra1',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true, 'default' => null],
            'Extra 1'
        )
        ->addColumn(
            'extra2',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true, 'default' => null],
            'Extra 2'
        )
        ->addColumn(
            'extra3',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true, 'default' => null],
            'Extra 3'
        )
        ->addForeignKey(
            $installer->getFkName('megagentory_stock', 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )
        ->addForeignKey(
            $installer->getFkName('megagentory_stock', 'inventory_id', 'megaventory_inventories', 'id'),
            'inventory_id',
            $installer->getTable('megaventory_inventories'),
            'id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        
        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_taxes'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => false],
            'Name'
        )
        ->addColumn(
            'percentage',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '10,5',
            ['default' => 0],
            'Percentage'
        )
        ->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true],
            'Description'
        )
        ->addColumn(
            'megaventory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Megaventory Id'
        );
        
        $installer->getConnection()->createTable($table);
        
        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_currencies'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => false],
            'Code'
        )
        ->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => true],
            'Description'
        )
        ->addColumn(
            'megaventory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Megaventory Id'
        );
        
        $installer->getConnection()->createTable($table);
                
        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_progress'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'messagedata',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            5000,
            ['nullable' => true],
            'Message Data'
        )
        ->addColumn(
            'flag',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false,"default" => 1],
            'Flag'
        )
        ->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Type'
        );
        
        $installer->getConnection()->createTable($table);
                
        $table = $installer->getConnection()
        ->newTable($installer->getTable('megaventory_bom'))
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )
        ->addColumn(
            'auto_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            1000,
            ['nullable' => false],
            'Message Data'
        )
        ->addColumn(
            'megaventory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Megaventory Id'
        )
        ->addColumn(
            'magento_product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'unsigned' => true, 'default' => 0],
            'Magento Product Id'
        )
        ->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )
        ->addColumn(
            'megaventory_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            500,
            ['nullable' => false],
            'Megaventory SKU'
        )
        ->addForeignKey(
            $installer->getFkName('megagentory_bom', 'magento_product_id', 'catalog_product_entity', 'entity_id'),
            'magento_product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
        
        $installer->getConnection()->createTable($table);
        
        $installer->getConnection()->addColumn(
            $installer->getTable('customer_entity'),
            'mv_supplierclient_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Megaventory Id'
                ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('catalog_category_entity'),
            'mv_productcategory_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Megaventory Id'
                ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('catalog_product_entity'),
            'mv_product_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Megaventory Id'
                ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'mv_salesorder_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => true,
                        'comment' => 'Megaventory Id'
                ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'mv_inventory_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Megaventory Inventory Id'
                ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'mv_inventory_id',
            [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Megaventory Inventory Id'
                ]
        );
        
        $configTable = $setup->getTable('core_config_data');
        
        $setup->getConnection()->update(
            $configTable,
            ['value' => new \Zend_Db_Expr('value*24')],
            ['path = ?' => \Magento\Customer\Model\Customer::XML_PATH_CUSTOMER_RESET_PASSWORD_LINK_EXPIRATION_PERIOD]
        );
        
        $installer->endSetup();
    }
}
