<?php

namespace Mv\Megaventory\Helper;

class Common extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SHIPPINGSKU  = 'shipping_service_01';
    const DISCOUNTSKU  = 'discount_01';
    
    protected $_scopeConfig;
    protected $_moduleList;
        
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_moduleList = $moduleList;
        
        parent::__construct($context);
    }
            
    public function isMegaventoryEnabled()
    {
        $mvIntegration = $this->_scopeConfig->getValue('megaventory/general/enabled');
        $mvApiUrl = $this->_scopeConfig->getValue('megaventory/general/apiurl');
        $mvApiKey = $this->_scopeConfig->getValue('megaventory/general/apikey');
    
        if ($mvIntegration == '1' && $mvApiUrl != null && $mvApiKey != null) {
            return true;
        }
    
        return false;
    }
    
    public function getExtensionVersion()
    {
        $mvModule = $this->_moduleList->getOne("Mv_Megaventory");
        return $mvModule['setup_version'];
    }
}
