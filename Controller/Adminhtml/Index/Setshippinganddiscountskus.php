<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class Setshippinganddiscountskus extends \Magento\Backend\App\Action
{
    protected $_resourceConfig;
    protected $_attributeFactory;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_attributeFactory = $attributeFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    public function execute()
    {
        $shippingSKU = $this->getRequest()->getPost('shippingSKU');
        $discountSKU = $this->getRequest()->getPost('discountSKU');
        $magentoId = $this->getRequest()->getPost('magentoId');
        
        $this->_resourceConfig->saveConfig('megaventory/general/shippingproductsku', $shippingSKU, 'default', 0);
        $this->_resourceConfig->saveConfig('megaventory/general/discountproductsku', $discountSKU, 'default', 0);
        $this->_resourceConfig->saveConfig('megaventory/general/magentoid', $magentoId, 'default', 0);
        
        //add supplier attribute in config data
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
        }
        
        $this->_cacheTypeList->cleanType('config');
        
        $result = [
                'attribute_code'=>'ok'
        ];
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
