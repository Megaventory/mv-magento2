<?php

namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    protected $_inventoryCollectionFactory;

    public function __construct(
        \Mv\Megaventory\Model\ResourceModel\Inventories\CollectionFactory $inventoryCollectionFactory
    ) {
        $this->_inventoryCollectionFactory = $inventoryCollectionFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.0', '>=') && version_compare($context->getVersion(), '1.3.0', '<')) {
            $defaultInventories = $this->_inventoryCollectionFactory->create()
             ->addFieldToFilter('isdefault', 1);
            foreach ($defaultInventories as $inventory) {
                $inventory->setStockSourceCode('default');
                $inventory->save();
            }
        }
    }
}
