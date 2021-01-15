<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UpdateSupplierSettings extends \Magento\Backend\App\Action
{

    protected $_resourceConfig;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_attributeFactory;
    
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_attributeFactory = $attributeFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $magentoSupplierAttributeCode = $this->getRequest()->getPost('magento_supplier_code');
        
        if (!empty($magentoSupplierAttributeCode)) {
            $attribute = $this->_attributeFactory->create()->loadByCode('catalog_product', $magentoSupplierAttributeCode);
            
            if (!$attribute->getId()) {
                $result = [
                        'attribute_code'=>'notok',
                        'message'=>'There is no attribute with this code'
                        ];
                
                return $this->_resultJsonFactory->create()->setData($result);
            }
            
            $frontendInput = $attribute->getFrontendInput();
            
            if ($frontendInput != 'text' && $frontendInput != 'select') {
                $result = [
                        'attribute_code'=>'notok',
                        'message'=>'Supplier attribute must be of frontend type Text or Dropdown'
                        ];
                
                return $this->_resultJsonFactory->create()->setData($result);
            }
                
            $this->_resourceConfig->saveConfig('megaventory/general/supplierattributecode', $magentoSupplierAttributeCode, 'default', 0);
            $this->_cacheTypeList->cleanType('config');
            
            $result = [
                    'attribute_code'=>'ok',
                    'message'=>'Supplier attribute setting updated successfully'
            ];
            
            return $this->_resultJsonFactory->create()->setData($result);
        } else {//delete it
        
            $result = [
                        'attribute_code'=>'notok',
                        'message'=>'Supplier Attribute setting deleted'
                        ];
            $this->_resourceConfig->deleteConfig('megaventory/general/supplierattributecode', 'default', 0);
            $this->_cacheTypeList->cleanType('config');
            
            return $this->_resultJsonFactory->create()->setData($result);
        }
    }
}
