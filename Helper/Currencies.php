<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Currencies extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    private $_mvHelper;
    private $_currenciesLoader;
    private $_currenciesCollection;
    private $_rateCollection;
    private $_resource;
    private $_messageManager;
    private $_backendUrl;
    private $APIKEY;

    protected $_registry;
    protected $logger;
    protected $mvLogFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $mvHelper,
        \Mv\Megaventory\Model\CurrenciesFactory $currenciesLoader,
        \Mv\Megaventory\Model\ResourceModel\Currencies\Collection $currenciesCollection,
        \Magento\Tax\Model\ResourceModel\Calculation\Rate\Collection $rateCollection,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Registry $registry,
        LogFactory $mvLogFactory,
        Logger $logger
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_mvHelper = $mvHelper;
        $this->_currenciesLoader = $currenciesLoader;
        $this->_currenciesCollection = $currenciesCollection;
        $this->_rateCollection = $rateCollection;
        $this->_resource = $recource;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
        $this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
                
        $this->_registry = $registry;
        $this->mvLogFactory = $mvLogFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }
    
    public function getCurrencies()
    {
        return $this->_currenciesCollection->load();
    }
    
    public function addMagentoCurrencies()
    {
    
        $defaultCurrencyCode = $this->_scopeConfig->getValue('currency/options/default');
        $allowedCurrencies = $this->_scopeConfig->getValue('currency/options/allow');
    
        $currencyCodes = explode(',', $allowedCurrencies);
    
        $totals = 0;
        foreach ($currencyCodes as $currencyCode) {
                $mvCurrency = [
                        'CurrencyCode' => $currencyCode,
                        'CurrencyDescription' => $currencyCode,
                        'CurrencySymbol' => '',
                        'CurrencyIsDefault' => false,
                        'CurrencyInReports' => 'true',
                ];
                    
                $data =
                [
                        'APIKEY' => $this->APIKEY,
                        'mvCurrency' => $mvCurrency,
                        'mvRecordAction' => 'Insert'
                ];
                    
                $json_result = $this->_mvHelper->makeJsonRequest($data, 'CurrencyUpdate', 0);
                    
                $errorCode = $json_result['ResponseStatus']['ErrorCode'];
                    
                if ($errorCode == 0) {
                    $totals++;
                }
                                    
                $newMVCurrency = $this->_currenciesLoader->create()->load($currencyCode, 'code');
                $newMVCurrency->setData('code', $currencyCode);
                $newMVCurrency->setData('description', $currencyCode);
                $newMVCurrency->setData('megaventory_id', $json_result['entityID']);
                $newMVCurrency->save();
        }
    
        $message = 'Added to Megaventory '.$totals.' currencies'.$this->_registry->registry('tickImage');
        $this->_mvHelper->sendProgress(14, $message, '0', 'entities', true);
    
        return $totals;
    }
    
    private function checkIfCurrencyExists($mvCurrency)
    {
    
        $currency = $this->_currenciesLoader->create()->load($mvCurrency['CurrencyID'], 'megaventory_id');
        if (!$currency) {
            return false;
        }
    
        return $currency;
    }
    
    public function addSingleCurrency($currencyCode)
    {
        $mvCurrency = [
                'CurrencyCode' => $currencyCode,
                'CurrencyDescription' => $currencyCode,
                'CurrencySymbol' => '',
                'CurrencyIsDefault' => false,
                'CurrencyInReports' => 'true',
        ];
            
        $data =
        [
                'APIKEY' => $this->APIKEY,
                'mvCurrency' => $mvCurrency,
                'mvRecordAction' => 'Insert'
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'CurrencyUpdate', 0);
            
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
            
        $newMVCurrency = $this->_currenciesLoader->create()->load($currencyCode, 'code');
        $newMVCurrency->setData('code', $currencyCode);
        $newMVCurrency->setData('description', $currencyCode);
        $newMVCurrency->setData('megaventory_id', $json_result['entityID']);
        $newMVCurrency->save();
    }
}
