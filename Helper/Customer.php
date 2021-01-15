<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    private $_resourceConfig;
    private $_mvHelper;
    private $_customerLoader;
    private $_resource;
    private $_messageManager;
    private $_backendUrl;
    private $APIKEY;
    
    protected $logger;
    protected $mvLogFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        Data $mvHelper,
        \Magento\Customer\Model\CustomerFactory $customerLoader,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        LogFactory $mvLogFactory,
        Logger $logger
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_resourceConfig = $resourceConfig;
        $this->_mvHelper = $mvHelper;
        $this->_customerLoader = $customerLoader;
        $this->_resource = $recource;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
        $this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
                
        $this->mvLogFactory = $mvLogFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function addCustomer($customer)
    {
        
        $megaVentoryId = $customer->getData('mv_supplierclient_id');
        
        if ($customer->getFirstname()) {
            $firstName =  $customer->getFirstname(). " ";
        } else {
            $firstName = "";
        }

        if ($customer->getLastname()) {
            $lastName = $customer->getLastname();
        } else {
            $lastName = "";
        }
        
        if ($firstName == null && $lastName == null) {
            $name = 'guest';
            $clientComments = '';
                
            $mvCustomerId = $this->_scopeConfig->getValue('megaventory/general/defaultguestid');//hard coded guest customer
                
            return $mvCustomerId;
        } else {
            $name = $firstName.$lastName;
                
            //get customer store
            $createdAt = $customer->getCreated_at();
            $webSiteId = $customer->getWebsite_id();
            $storeId = $customer->getStore_id();
            $storeViewName = $customer->getCreated_in();
            $clientComments = ''.'created at:'.$createdAt.',website:'.$webSiteId.',store:'.$storeId.',storeview:'.$storeViewName;
        
            if (isset($megaVentoryId) && $megaVentoryId!=null) { //it is an update
                $mvCustomerId = $megaVentoryId;
                $mvRecordAction = 'Update';
            } else {//it is an insert
            
                $mvCustomerId = '0';
                $mvRecordAction = 'Insert';
            }
        
            $supplierClientBillingAddress = '';
            $supplierClientShippingAddress1 = '';
            $supplierClientShippingAddress2 = '';
            $supplierClientPhone1 = '';
            $supplierClientFax = '';
            $primaryBillingAddress = $customer->getPrimaryBillingAddress();
            $primaryShippingAddress = $customer->getPrimaryShippingAddress();
                
            //TODO
            foreach ($customer->getAddressesCollection() as $address) {
                $flag = false;
        
                $telephone = $address->getTelephone();
        
                $fax = $address->getFax();
                $addressId = $address->getEntity_id();
        
                $isDefaultBilling = $address->getData('is_default_billing');
                if ((isset($isDefaultBilling) && $isDefaultBilling) || ($primaryBillingAddress && $primaryBillingAddress->getId() == $addressId)) {
                    $supplierClientBillingAddress = $address->format('oneline');
                    $supplierClientPhone1 = $telephone;
                    $supplierClientFax = $fax;
                    $flag = true;
                }
        
                $isDefaultShipping = $address->getData('is_default_shipping');
                if ((isset($isDefaultShipping) && $isDefaultShipping) || ($primaryShippingAddress && $primaryShippingAddress->getId() == $addressId)) {
                    $supplierClientShippingAddress1 = $address->format('oneline');
                    $flag = true;
                }
        
                if (!$flag) {
                    $supplierClientShippingAddress2 = $address->format('oneline');
                }
            }
        
            $data =  [
                    'APIKEY' => $this->APIKEY,
                    'mvSupplierClient' =>
                    ['SupplierClientID' => $mvCustomerId,
                            'SupplierClientType' => '2',
                            'SupplierClientName' => $name . " " . $customer->getEmail(),
                            'mvContacts' =>  ['ContactIsPrimary' => "False" ],
                            'SupplierClientBillingAddress' => $supplierClientBillingAddress,
                            'SupplierClientShippingAddress1' => $supplierClientShippingAddress1,
                            'SupplierClientShippingAddress2' => $supplierClientShippingAddress2,
                            'SupplierClientPhone1' => $supplierClientPhone1,
                            'SupplierClientPhone2' => "",
                            'SupplierClientFax' => $supplierClientFax,
                            'SupplierClientIM' => "",
                            'SupplierClientEmail' => $customer->getEmail(),
                            'SupplierClientTaxID' => "",
                            'SupplierClientComments' => $clientComments
                    ],
                    'mvRecordAction' => $mvRecordAction,
                    'mvGrantPermissionsToAllUsers' => true];
                 
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientUpdate', $customer->getId());
                
            $errorCode = $json_result['ResponseStatus']['ErrorCode'];
            if ($errorCode == '0') {//no errors
                if (strcmp('Insert', $mvRecordAction) == 0) {
                    $this->updateCustomer($customer->getId(), $json_result ['mvSupplierClient'] ['SupplierClientID']);
                    return $json_result ['mvSupplierClient'] ['SupplierClientID'];
                }

                return $json_result['entityID'];
            } else {
                $entityId = $json_result['entityID'];
                if (!empty($entityId) && $entityId > 0) {
                    if (strpos($json_result['ResponseStatus']['Message'], 'and was since deleted') !== false) {
                        $result = [
                                'mvCustomerId' => $json_result['entityID'],
                                'errorcode' => 'isdeleted'
                        ];
                        return $result;
                    } elseif ($json_result['InternalErrorCode'] == 'SupplierClientNameAlreadyExists') {
                        $this->updateCustomer($customer->getId(), $entityId);
                        $data['mvSupplierClient']['SupplierClientID'] = $entityId;
                        $data['mvRecordAction'] = 'Update';
                        $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientUpdate', $customer->getId());
                        return $entityId;
                    } elseif ((strpos($json_result['ResponseStatus']['Message'], 'already exists') !== false)) {
                        $data['mvSupplierClient']['SupplierClientName'] .= ' '.$customer->getEmail();
                        $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientUpdate', $customer->getId());

                        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
                        if ($errorCode == '0') {//no errors
                            if (strcmp('Insert', $mvRecordAction) == 0) {
                                $this->updateCustomer($customer->getId(), $json_result ['mvSupplierClient'] ['SupplierClientID']);
                                return $json_result ['mvSupplierClient'] ['SupplierClientID'];
                            }

                            return $json_result['entityID'];
                        }
                    } else {
                        $this->updateCustomer($customer->getId(), $entityId);
                        $data['mvSupplierClient']['SupplierClientID'] = $entityId;
                        $data['mvRecordAction'] = 'Update';
                        $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientUpdate', $customer->getId());
                        return $entityId;
                    }
                }
            }
        }

        return 0;
    }
    
    public function deleteCustomer($customer)
    {
        $megaVentoryId = $customer->getData('mv_supplierclient_id');
    
        if (isset($megaVentoryId) && $megaVentoryId!=null) { //it is an update
            $data = [
                    'APIKEY' => $this->APIKEY,
                    'SupplierClientIDToDelete'=>$megaVentoryId,
                    'SupplierClientDeleteAction'=> 'DefaultAction',
            ];
    
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientDelete', $customer->getId());
        }
    }
    
    public function addDefaultGuestCustomer()
    {
        $data =
        [
                'APIKEY' => $this->APIKEY,
                'mvSupplierClient' =>
                ['SupplierClientID' => 0,
                        'SupplierClientType' => '2',
                        'SupplierClientName' => 'Magento Guest',
                        'mvContacts' =>  ['ContactIsPrimary' => "False" ],
                        'SupplierClientBillingAddress' => '',
                        'SupplierClientShippingAddress1' => '',
                        'SupplierClientShippingAddress2' => '',
                        'SupplierClientPhone1' => '',
                        'SupplierClientPhone2' => '',
                        'SupplierClientFax' => '',
                        'SupplierClientIM' => '',
                        'SupplierClientEmail' => '',
                        'SupplierClientTaxID' => '',
                        'SupplierClientComments' => ''
                ],
                'mvRecordAction' => 'Insert'
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'SupplierClientUpdate', 0);
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode == 0) {
            $defaultGuestId = $json_result['mvSupplierClient']['SupplierClientID'];
            $this->_resourceConfig->saveConfig('megaventory/general/defaultguestid', $defaultGuestId, 'default', 0);
            $this->_mvHelper->sendProgress(13, 'Default Guest account added successfully!', '0', 'defaultguest', true);
        } else {
            $entityId = $json_result['entityID'];
            if (!empty($entityId) && $entityId > 0) {
                $defaultGuestId = $entityId;
                $this->_resourceConfig->saveConfig('megaventory/general/defaultguestid', $defaultGuestId, 'default', 0);
                $this->_mvHelper->sendProgress(13, 'Default Guest account added successfully!', '0', 'defaultguest', true);
            } else {
                $this->_mvHelper->sendProgress(13, 'There was an error creating Default Guest account', '0', 'defaultguest', true);
            }
        }

        return $errorCode;
    }
    
    public function updateCustomer($magentoCustomerId, $mvCustomerId)
    {
        
        $connection = $this->_resource->getConnection();
        $table = $this->_resource->getTableName('customer_entity');
        $sql_insert = "update ".$table." set mv_supplierclient_id = ".$mvCustomerId." where entity_id = ".$magentoCustomerId;
        $connection->query($sql_insert);
    }
}
