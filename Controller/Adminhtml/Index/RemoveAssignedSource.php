<?php

namespace Mv\Megaventory\Controller\Adminhtml\Index;

class RemoveAssignedSource extends \Magento\Backend\App\Action
{
    private $_mvInventory;
    private $_resultJsonFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Mv\Megaventory\Model\InventoriesFactory $mvInventoryFactory
    ) {
        $this->_mvInventory = $mvInventoryFactory;
        $this->_resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $inventoryId = (int)$this->getRequest()->getParam('inventory_id');
        $mvInventory = $this->_mvInventory->create()->load($inventoryId);
        $resultJson = $this->_resultJsonFactory->create();
        try {
            $mvInventory->setData('stock_source_code');
            $mvInventory->setCountsInGlobalStock(0);
            $mvInventory->save();
            $resultJson->setData(
                [
                    'status_label'=>'Success',
                    'message'=>'Removed inventory source from megaventory location "'.$mvInventory->getName().'"'
                ]
            );
        } catch (\Exception $e) {
            $resultJson->setData(['status_label'=>'Error','message'=>$e->getMessage()]);
        }

        return $resultJson;
    }
}
