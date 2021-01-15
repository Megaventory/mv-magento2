<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class GetInventories extends \Magento\Backend\App\Action
{
    protected $_inventoriesHelper;
    protected $_resourceConfig;
    protected $_resource;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_resourceConfig = $resourceConfig;
        $this->_resource = $recource;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        
        parent::__construct($context);
    }

    public function execute()
    {
        $inventories = $this->_inventoriesHelper->getInventories();
        foreach ($inventories as $inventory) {
            $options[] = ['value' => $inventory->getMegaventoryId() , 'text' => $inventory->getName()];
        }
        
        $result = ['options'=> $options];
        
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
