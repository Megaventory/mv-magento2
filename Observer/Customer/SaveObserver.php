<?php

namespace Mv\Megaventory\Observer\Customer;

use \Magento\Framework\Event\ObserverInterface;

class SaveObserver implements ObserverInterface
{
    private $_mvCustomerHelper;
    protected $_commonHelper;
    private $_backendUrl;
    private $_messageManager;
    protected $_logger;
    
    public function __construct(
        \Mv\Megaventory\Helper\Customer $mvCustomerHelper,
        \Mv\Megaventory\Helper\Common $commonHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_mvCustomerHelper = $mvCustomerHelper;
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
        
        $event = $observer->getEvent();
        $customer = $event->getCustomer();
        
        $result = $this->_mvCustomerHelper->addCustomer($customer);
        
        if (is_array($result)) {
            $undeleteUrl = $this->_backendUrl->getUrl("megaventory/index/undeleteEntity");
            $this->_messageManager->addError('Customer '.$customer->getName().' is flagged as deleted in Megaventory. Presse <a onclick="MegaventoryManager.undeleteEntity(\'' . $undeleteUrl  .'\','.$result['mvCustomerId'].',\'supplierclient\')" href="javascript:void(0);">here</a> if you want to automatically undelete it');
        }
    }
}
