<?php
/**
 * Copyright © 2016 Megaventory. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
   
    protected $_mvHelper;
    public function __construct(\Mv\Megaventory\Helper\Data $mvHelper)
    {
        $this->_mvHelper = $mvHelper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->_mvHelper->resetMegaventoryData();
        $this->_mvHelper->deleteCredetials();

        $configTable = $setup->getTable('core_config_data');

        $setup->getConnection()->delete(
            $configTable,
            ['path = ?' => 'cataloginventory/item_options/manage_stock']
        );
        
        $setup->getConnection()->insert(
            $configTable,
            [
                'scope' => 'default',
                'scope_id' => '0',
                'path' => 'cataloginventory/item_options/manage_stock',
                'value' => '1'
            ]
        );
    }
}
