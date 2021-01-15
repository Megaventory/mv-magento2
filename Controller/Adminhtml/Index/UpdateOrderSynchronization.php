<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UpdateOrderSynchronization extends \Magento\Backend\App\Action
{

    protected $_resourceConfig;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;

    protected $_scopeConfig;
    
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $bCount = $this->getRequest()->getPost('value');
        $bCount == 'true' ? $orderSynchronization = '1' : $orderSynchronization = '0';
        
        $algorithmCode = $this->_scopeConfig->getValue('megaventory/orders/source_selection_algorithm_code');
        $isAlgorithmCodeNotSet = ($algorithmCode === null);
        $isOrderSynchronizationEnabled = ($orderSynchronization == '1');

        if (($isOrderSynchronizationEnabled) && ($isAlgorithmCodeNotSet)) {
            $this->_resourceConfig->saveConfig(
                'megaventory/orders/source_selection_algorithm_code',
                'priority',
                'default',
                0
            );
        }
        
        $this->_resourceConfig->saveConfig(
            'megaventory/general/ordersynchronization',
            $orderSynchronization,
            'default',
            0
        );
        $this->_cacheTypeList->cleanType('config');
        
        return $this->_resultJsonFactory->create()->setData([]);
    }
}
