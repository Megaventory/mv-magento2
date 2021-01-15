<?php

namespace Mv\Megaventory\Controller\Adminhtml\Index;

class ExportStock extends \Magento\Backend\App\Action
{
    protected $_inventoriesLoader;
    protected $_inventoriesCollection;
    protected $_mvProductHelper;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_directoryList;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Mv\Megaventory\Model\ResourceModel\Inventories\CollectionFactory $inventoriesCollection,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_mvProductHelper = $mvProductHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_directoryList = $directoryList;
        $this->_inventoriesCollection = $inventoriesCollection;

        parent::__construct($context);
    }

    public function execute()
    {
        $enabledLocations = $this->_inventoriesCollection->create()->addFieldToFilter('stock_source_code',['notnull'=>true]);
        $startingIndex = (int) $this->getRequest()->getParam('startingIndex');
        $isInitialization = ((int)$this->getRequest()->getParam('init') === 1);
        $adjIssued = ($this->getRequest()->getParam('adjustment') == 'true');

        $results = [];

        if ($isInitialization) {
            $locations = $this->getRequest()->getParam('locations');

            foreach ($locations as $mvInventoryId => $sourceCode) {
                $location = $this->_inventoriesLoader->create()->load((int)$mvInventoryId, 'megaventory_id');
                $location->setStockSourceCode($sourceCode);
                $location->save();
                $results = $this->_mvProductHelper->exportStock(
                    $mvInventoryId,
                    $startingIndex,
                    $this->_directoryList,
                    $sourceCode,
                    $adjIssued
                );
                if ($results['value'] == 'Error') {
                    break;
                }
            }
        } else {
            foreach ($enabledLocations as $location) {
                $results = $this->_mvProductHelper->exportStock(
                    $location->getMegaventoryId(),
                    $startingIndex,
                    $this->_directoryList,
                    $location->getStockSourceCode(),
                    $adjIssued
                );
                if ($results['value'] == 'Error') {
                    break;
                }
            }
        }

        return $this->_resultJsonFactory->create()->setData($results);
    }
}
