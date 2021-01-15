<?php

namespace Mv\Megaventory\Observer\Admin;

use \Magento\Framework\Event\ObserverInterface;

class LoginObserver implements ObserverInterface
{
    
    protected $_scopeConfig;
    protected $_adminSession;
    protected $_mvHelper;
    protected $_logger;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_adminSession = $adminSession;
        $this->_mvHelper = $mvHelper;
        $this->_logger = $logger;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        $apikey = $this->_scopeConfig->getValue('megaventory/general/apikey');
        $apiurl = $this->_scopeConfig->getValue('megaventory/general/apiurl');
        
        if (empty($apikey) || empty($apiurl)) {
            return;
        }
        
        $accountSettings = $this->_mvHelper->getMegaventoryAccountSettings('All');
        
        if ($accountSettings != false) {
            foreach ($accountSettings as $index => $accountSetting) {
                $settingName = $accountSetting['SettingName'];
                $settingValue = $accountSetting['SettingValue'];
                $this->_adminSession->setData('mv_'.$settingName, $settingValue);
            }
        }
    }
}
