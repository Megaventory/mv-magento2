<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Taxes extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    private $_mvHelper;
    private $_taxesLoader;
    private $_taxesCollection;
    private $_rateCollection;
    private $_resource;
    private $_messageManager;
    private $_backendUrl;
    private $APIKEY;
    
    protected $logger;
    protected $mvLogFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $mvHelper,
        \Mv\Megaventory\Model\TaxesFactory $taxesLoader,
        \Mv\Megaventory\Model\ResourceModel\Taxes\Collection $taxesCollection,
        \Magento\Tax\Model\ResourceModel\Calculation\Rate\Collection $rateCollection,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        LogFactory $mvLogFactory,
        Logger $logger
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_mvHelper = $mvHelper;
        $this->_taxesLoader = $taxesLoader;
        $this->_taxesCollection = $taxesCollection;
        $this->_rateCollection = $rateCollection;
        $this->_resource = $recource;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
        $this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
                
        $this->mvLogFactory = $mvLogFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }
    
    public function getTaxes()
    {
        return $this->_taxesCollection->load();
    }
    
    public function synchronizeTaxes($sendProgress = true)
    {
        $data =
        [
                'APIKEY' => $this->APIKEY
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'TaxGet', 0);
    
        $mvTaxes = $json_result['mvTaxes'];
    
        $i = 0;
    
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('megaventory_taxes');
        
        $deleteTaxes = 'delete from '.$tableName;
        $connection->query($deleteTaxes);
    
        //import taxes from megaventory
        foreach ($mvTaxes as $mvTax) {
            $tax = $this->checkIfTaxExists($mvTax);
            if ($tax == false) {
                $this->insertTax($mvTax);
            } else {
                $this->updateTax($tax, $mvTax);
            }

            $i++;
        }
    
        //send extra tax rates to megaventory
        $taxRates = $this->_rateCollection->load();
    
        foreach ($taxRates as $taxRate) {
            $percentage = $taxRate->getRate();
            if ($this->getTaxByPercentage($percentage)==false) {
                $newMvTax =  [
                        'TaxID'=> 0,
                        'TaxName'=> $taxRate->getCode(),
                        'TaxDescription'=>$taxRate->getTax_country_id().' '.$taxRate->getTax_Region_id().' '.$taxRate->getTax_postcode(),
                        'TaxValue'=>$percentage
                ];
                $data['mvTax'] = $newMvTax;
                $data['mvRecordAction'] = 'Insert';
    
                $json_result = $this->_mvHelper->makeJsonRequest($data, 'TaxUpdate', 0);
                $errorCode = $json_result['ResponseStatus']['ErrorCode'];
                    
                if ($errorCode == 0) {
                    $newMvTax = $json_result['mvTax'];
                    $this->insertTax($newMvTax);
                }
            }
        }
        
        if ($sendProgress) {
            $this->_mvHelper->sendProgress(15, '<br>Tax rates synchronized successfully', '0', 'taxes', true);
        }
        
        return $i;
    }
    
    private function insertTax($mvTax)
    {
        $mvID = $mvTax['TaxID'];
        $mvTaxName = $mvTax['TaxName'];
        $mvTaxDescription = $mvTax['TaxDescription'];
        $mvTaxValue = $mvTax['TaxValue'];
        
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('megaventory_taxes');
        
        $sql_insert = 'insert into '.$tableName.' (name, description, percentage,megaventory_id) values ("'.$mvTaxName.'","'.$mvTaxDescription.'","'.$mvTaxValue.'","'.$mvID.'")';
        $connection->query($sql_insert);
    }
    
    private function updateTax($tax, $mvTax)
    {
        $tax->setData('name', $mvTax['TaxName']);
        $tax->setData('description', $mvTax['TaxDescription']);
        $tax->setData('percentage', $mvTax['TaxValue']);
        $tax->save();
    }
    
    private function checkIfTaxExists($mvTax)
    {
        $tax = $this->_taxesLoader->create()->load($mvTax['TaxID'], 'megaventory_id');
        
        $id = $tax->getData('id');
        if (!isset($id)) {
            return false;
        }
    
        return $tax;
    }
    
    public function getTaxByPercentage($percentage)
    {
   
        $tax = $this->_taxesCollection
        ->addFieldToFilter('percentage', ['gt' => $percentage - 0.25])
        ->addFieldToFilter('percentage', ['lt' => $percentage + 0.25])
        ->getFirstItem();
    
        $id = $tax->getData('id');
        if (!isset($id)) {
            return false;
        }
    
        return $tax;
    }
    
    public function addMagentoTax($percentage)
    {
        $taxName = $percentage;
        $taxRate = $this->_rateCollection->addFieldToFilter('rate', ['eq' => $percentage])->getFirstItem();
    
        if (!empty($taxRate) && $taxRate->getId()) {
            $taxName = $taxRate->getCode();
        }
    
        $mvTax = [
                'TaxID' => '0',
                'TaxName' => $taxName,
                'TaxDescription' => '',
                'TaxValue' => $percentage
        ];
        $data =
        [
                'APIKEY' => $this->APIKEY,
                'mvTax' => $mvTax,
                'mvRecordAction' => 'Insert'
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'TaxUpdate', 0);
        $megaventoryId = $json_result ['mvTax'] ['TaxID'];
        if (isset($megaventoryId)) {
            $mvTax['TaxID'] = $megaventoryId;
    
            $this->insertTax($mvTax);
                
            return $megaventoryId;
        }
    
        return false;
    }
}
