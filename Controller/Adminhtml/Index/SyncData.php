<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class SyncData extends \Magento\Backend\App\Action
{
    protected $_scopeConfig;
    protected $_resourceConfig;
    protected $_resource;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_registry;
    protected $_adminSession;
    
    protected $_mvHelper;
    protected $_categoryHelper;
    protected $_productHelper;
    protected $_customerHelper;
    protected $_currenciesHelper;
    protected $_taxesHelper;

    protected $_apiDocumentTypeAdapter;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Mv\Megaventory\Helper\Category $categoryHelper,
        \Mv\Megaventory\Helper\Product $productHelper,
        \Mv\Megaventory\Helper\Customer $customerHelper,
        \Mv\Megaventory\Helper\Currencies $currenciesHelper,
        \Mv\Megaventory\Helper\Taxes $taxesHelper,
        \Mv\Megaventory\Model\ApiAdapter\DocumentTypeAdapter $documentTypeAdapter
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConfig = $resourceConfig;
        $this->_resource = $recource;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_registry = $registry;
        $this->_adminSession = $adminSession;
        
        $this->_mvHelper = $mvHelper;
        $this->_categoryHelper = $categoryHelper;
        $this->_productHelper = $productHelper;
        $this->_customerHelper = $customerHelper;
        $this->_currenciesHelper = $currenciesHelper;
        $this->_taxesHelper = $taxesHelper;

        $this->_apiDocumentTypeAdapter = $documentTypeAdapter;
        
        parent::__construct($context);
    }

    public function execute()
    {
        session_write_close();
        $megaventoryIntegration = $this->_scopeConfig->getValue('megaventory/general/enabled');
        if ($megaventoryIntegration == '0') {
            $result = ['message'=>'not_enabled'];
            return $this->_resultJsonFactory->create()->setData($result);
        }

        $this->_mvHelper->updateHeartBeatTimestamp();

        
        static $step = 1;
        $totalSteps = 3;
        
        $serverTime = '';//time();
        $tickImg = '';//'<img src="'.Mage::getDesign()->getSkinUrl('images/megaventory/accept.png').'" style="position:relative;top:1px;left:4px;"/>';
        $errorImg = '';//'<img src="'.Mage::getDesign()->getSkinUrl('images/megaventory/exclamation.png').'" style="position:relative;top:1px;left:4px;"/>';
        $this->_registry->register('tickImage', $tickImg);
        $this->_registry->register('errorImage', $errorImg);
        
        $result = [];
        $syncStep = $this->getRequest()->getParam('step');
        $page = $this->getRequest()->getParam('page');
        $imported = $this->getRequest()->getParam('imported');
        if (empty($imported)) {
            $imported = 0;
        }
                
        if ($syncStep == 'supporting') {
            $this->_mvHelper->sendProgress(10, '<br><strong>Step 1/'.$totalSteps.'</strong> Adding Supporting Entities to Megaventory', '0', 'entities', false);
            $shippingProductSku = $this->_scopeConfig->getValue('megaventory/general/shippingproductsku');
            $createdMessage = $this->_productHelper->addOrUpdateShippingProduct($shippingProductSku);
            if ($createdMessage !== true) {
                $this->_mvHelper->sendProgress(11, 'There was a problem inserting Shipping Product in Megaventory!'.$this->_registry->registry('errorImage'), '0', 'shippingproduct', false);
                $this->_mvHelper->sendProgress(12, $createdMessage, '0', 'shippingproduct', false);
                $result['nextstep'] = 'error';
                return $this->_resultJsonFactory->create()->setData($result);
            }

            $this->_mvHelper->sendProgress(11, 'Shipping Product added successfully!', '0', 'shippingproduct', true);

            $discountProductSku = $this->_scopeConfig->getValue('megaventory/general/discountproductsku');
                
            $createdMessage = $this->_productHelper->addOrUpdateDiscountProduct($discountProductSku);
            if ($createdMessage !== true) {
                $this->_mvHelper->sendProgress(12, 'There was a problem inserting Discount Product in Megaventory!'.$this->_registry->registry('errorImage'), '0', 'discountproduct', false);
                $this->_mvHelper->sendProgress(13, $createdMessage, '0', 'discountproduct', false);
                $result['nextstep'] = 'error';
                return $this->_resultJsonFactory->create()->setData($result);
            }

            $this->_mvHelper->sendProgress(12, 'Discount Product added successfully!', '0', 'discountproduct', true);
            
            $this->_customerHelper->addDefaultGuestCustomer();
            $this->_currenciesHelper->addMagentoCurrencies();
            $this->_taxesHelper->synchronizeTaxes();
            
            $result['currentstep'] = $syncStep;
            $result['nextstep'] = 'categories';
            $result['nextpage'] = 1;
            $result['imported'] = 0;
            return $this->_resultJsonFactory->create()->setData($result);
        } elseif ($syncStep == 'categories') {
            if ($page == '1') {
                $this->_mvHelper->sendProgress(20, '<br><strong>Step 2/'.$totalSteps.'</strong> Importing Categories to Megaventory..', '0', 'categories', false);
            }
                
            $import = $this->_categoryHelper->importCategoriesToMegaventory($page, $imported);
                
            if ($import === false) {
                $result['currentstep'] = $syncStep;
                $result['nextstep'] = 'products';
                $result['nextpage'] = '1';
                $result['imported'] = 0;
            } else {
                $result['currentstep'] = $syncStep;
                $result['nextstep'] = 'categories';
                $result['nextpage'] = $import['nextpage'];
                $result['imported'] = $import['imported'];
            }

            return $this->_resultJsonFactory->create()->setData($result);
        } elseif ($syncStep == 'products') {
            if ($page == '1') {
                $this->_mvHelper->sendProgress(30, '<br><strong>Step 3/'.$totalSteps.'</strong> Importing Products to Megaventory..', '0', 'products', false);
            }
            
            $import = $this->_productHelper->importProductsToMegaventory($page, $imported);
            
            if ($import === false) {
                $result['currentstep'] = $syncStep;
                $result['nextstep'] = 'finishing';
                $result['nextpage'] = '1';
                $result['imported'] = 0;
            } else {
                $result['currentstep'] = $syncStep;
                $result['nextstep'] = 'products';
                $result['nextpage'] = $import['nextpage'];
                $result['imported'] = $import['imported'];
            }

            return $this->_resultJsonFactory->create()->setData($result);
        } elseif ($syncStep == 'finishing') {
            $this->_apiDocumentTypeAdapter->reloadDocumentTypesFromApi(true);
            $syncTimestamp = time();
            $this->_mvHelper->sendProgress(40, '<br>Setup Wizard finished successfully at '.date(DATE_RFC2822, $syncTimestamp), '0', 'finish', true);
            $this->_mvHelper->sendProgress(41, 'Saving Set up data for later reference!', '0', 'saveddata', false);
                
            $this->_mvHelper->sendProgress(42, 'Done!'.$this->_registry->registry('tickImage'), '0', 'done', false);
                
            $this->_resourceConfig->saveConfig('megaventory/general/synctimestamp', $syncTimestamp, 'default', 0);
            $this->_resourceConfig->saveConfig('megaventory/general/setupreport', $this->_mvHelper->getProgressMessage(), 'default', 0);
            
            $this->_cacheTypeList->cleanType('config');
                
            $apikey = $this->_scopeConfig->getValue('megaventory/general/apikey');
            $apiurl = $this->_scopeConfig->getValue('megaventory/general/apiurl');
                
            if (!empty($apikey) && !empty($apiurl)) {
                $accountSettings = $this->_mvHelper->getMegaventoryAccountSettings('All');
        
                foreach ($accountSettings as $index => $accountSetting) {
                    $settingName = $accountSetting['SettingName'];
                    $settingValue = $accountSetting['SettingValue'];
                    $this->_adminSession->setData('mv_'.$settingName, $settingValue);
                }
            }

            $connection = $this->_resource->getConnection();
            $tableName = $this->_resource->getTableName('megaventory_progress');
            
            $lastMessageSql = 'SELECT id FROM '.$tableName.' ORDER BY id asc LIMIT 1';
                
            while (true) {
                $lastMessage = $connection->fetchOne($lastMessageSql);
                if ($lastMessage == false) {
                    break;
                }
                    
                sleep(2);
            }

            $this->_mvHelper->deleteInitialSyncFlags();

            $this->_registry->unregister('tickImage');
            $result['nextstep'] = 'finish';
            return $this->_resultJsonFactory->create()->setData($result);
        } elseif ($syncStep == 'error') {
            $this->_mvHelper->sendProgress(90, '<br>Setup Wizard did not finish succesfully.', '0', 'finisherror', true);
            $this->_mvHelper->sendProgress(100, 'Please refresh page and try again!', '0', 'done', false);
            
            $connection = $this->_resource->getConnection();
            $tableName = $this->_resource->getTableName('megaventory_progress');
            
            $lastMessageSql = 'SELECT id FROM '.$tableName.' ORDER BY id asc LIMIT 1';
        
            while (true) {
                $lastMessage = $connection->fetchOne($lastMessageSql);
                if ($lastMessage == false) {
                    break;
                }
                    
                sleep(2);
            }

            $this->_registry->unregister('tickImage');
            $this->_registry->unregister('errorImage');
            
            $result['nextstep'] = 'finisherror';
            return $this->_resultJsonFactory->create()->setData($result);
        }
    }
}
