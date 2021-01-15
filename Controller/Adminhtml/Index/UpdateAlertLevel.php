<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class UpdateAlertLevel extends \Magento\Backend\App\Action
{
    protected $_mvHelper;
    protected $_inventoriesHelper;
    protected $_scopeConfig;
    protected $_resultJsonFactory;
        
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_mvHelper = $mvHelper;
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        
        parent::__construct($context);
    }

    public function execute()
    {
        $mvInventoryId = $this->getRequest()->getParam('mv_inventory_id');
        $productId = $this->getRequest()->getParam('magento_product_id');
        $mvProductId = $this->getRequest()->getParam('mv_product_id');
        $alertLevel = $this->getRequest()->getParam('alertlevel');
        
        $alertData =
        [
                'APIKEY' => $this->_scopeConfig->getValue('megaventory/general/apikey'),
                'mvProductStockAlertsAndSublocationsList'=>
                [
                        'productID' => $mvProductId,
                        'mvInventoryLocationStockAlertAndSublocations' => [
                                'InventoryLocationID' => $mvInventoryId,
                                'StockAlertLevel' => $alertLevel
                        ]
                            
                ]
        ];
        
        $json_result = $this->_mvHelper->makeJsonRequest($alertData, 'InventoryLocationStockAlertAndSublocationsUpdate');
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        
        if ($errorCode == '0') {
            $magentoInventoryId = $this->_inventoriesHelper->getInventoryFromMegaventoryId($mvInventoryId)->getId();
            $result = $this->_inventoriesHelper->updateInventoryProductAlertValue($productId, $magentoInventoryId, $alertLevel);
        } else {
            $result = [];
        }
        
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
