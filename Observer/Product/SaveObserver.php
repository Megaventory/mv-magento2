<?php
namespace Mv\Megaventory\Observer\Product;

use \Magento\Framework\Event\ObserverInterface;

class SaveObserver implements ObserverInterface
{
    private $_mvProductHelper;
    protected $_commonHelper;
    private $_backendUrl;
    private $_messageManager;
    
    protected $_logger;
    
    public function __construct(
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Mv\Megaventory\Helper\Common $commonHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger //log injection
    ) {
        $this->_mvProductHelper = $mvProductHelper;
        $this->_commonHelper = $commonHelper;
        $this->_backendUrl = $backendUrl;
        $this->_messageManager = $messageManager;
    
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (! $this->_commonHelper->isMegaventoryEnabled()) {
            return;
        }
        $product = $observer->getEvent()->getProduct();
        if ($product != null) {
            $result = -1;
            $result = $this->_mvProductHelper->addProduct($observer->getEvent()->getProduct());
    
            if ($result == 0) {
                $logUrl = $this->_backendUrl->getUrl("megaventory/log/index");
                $this->_messageManager->addError('Product '.$product->getId().' did not updated in Megaventory. Please review <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details');
            }
        
            if (is_array($result)) {
                $undeleteUrl = $this->_backendUrl->getUrl("megaventory/index/undeleteEntity");
                $this->_messageManager->addError('Product with SKU '.$product->getSku().' is flagged as deleted in Megaventory. Presse <a onclick="MegaventoryManager.undeleteEntity(\'' . $undeleteUrl  .'\','.$result['mvProductId'].',\'product\')" href="javascript:void(0);">here</a> if you want to automatically undelete it');
            }
        }
    }
}
