<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UpdateMagentoId extends \Magento\Backend\App\Action
{
    
    protected $_resourceConfig;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = [
            'message'=>'Error in updating magento id'
        ];
        
        $magentoId = $this->getRequest()->getPost('magento_id');
        
        if (!empty($magentoId)) {
            $this->_resourceConfig->saveConfig('megaventory/general/magentoid', $magentoId, 'default', 0);
            $this->_cacheTypeList->cleanType('config');
            $result['message']='Magento Id updated successfully!';
        }
        
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
