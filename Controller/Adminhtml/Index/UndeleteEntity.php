<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UndeleteEntity extends \Magento\Backend\App\Action
{

    protected $_scopeConfig;
    protected $_mvHelper;
    protected $_resultJsonFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $contextHelper,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_scopeConfig = $contextHelper->getScopeConfig();
        $this->_mvHelper = $mvHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $mvId = $this->getRequest()->getPost('mvId');
        $mvEntityType = $this->getRequest()->getPost('mvEntityType');
        
        if ($mvEntityType == 'product') {
            $idString = 'ProductIDToUndelete';
            $operation = 'ProductUndelete';
        }
        
        if ($mvEntityType == 'category') {
            $idString = 'ProductCategoryIDToUndelete';
            $operation = 'ProductCategoryUndelete';
        }
        
        if ($mvEntityType == 'supplierclient') {
            $idString = 'SupplierClientIDToUndelete';
            $operation = 'SupplierClientUndelete';
        }
        
        $data = [
                'APIKEY' => $this->_scopeConfig->getValue('megaventory/general/apikey'),
                $idString => $mvId
        ];
        
        $this->_mvHelper->makeJsonRequest($data, $operation);
        
        return $this->_resultJsonFactory->create()->setData([]);
    }
}
