<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    private $_mvHelper;
    private $_categoryLoader;
    private $_categoryCollectionFactory;
    private $_resource;
    private $_messageManager;
    private $_backendUrl;
    private $_registry;
    private $APIKEY;
    
    protected $logger;
    protected $mvLogFactory;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $mvHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryLoader,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Registry $registry,
        LogFactory $mvLogFactory,
        Logger $logger
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_mvHelper = $mvHelper;
        $this->_categoryLoader = $categoryLoader;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_resource = $recource;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
        $this->_registry = $registry;
        $this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
                
        $this->mvLogFactory = $mvLogFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function addCategory($category)
    {
        
        $id = $category->getData('mv_productcategory_id');
        
        if (isset($id) && $id != null) { // it is an update
            $action = 'Update';
        } else {
            $id = '0';
            $action = 'Insert';
        }
        
        if (strcmp($action, "Insert") == 0) {
            $name = $this->createCategoryName($category);
            $descr = $category->getDescription();
        
            if (isset($descr) && $descr != null) {
                $description = $descr;
            } else {
                $description = '';
            }
        
            $data =  [
                    'APIKEY' => $this->APIKEY,
                    'mvProductCategory' =>  [
                            'ProductCategoryID' => $id,
                            'ProductCategoryName' => $name,
                            'ProductCategoryDescription' => $description ],
                    'mvRecordAction' => $action ];
        
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'ProductCategoryUpdate', $category->getId());
        
            $errorCode = $json_result['ResponseStatus']['ErrorCode'];
            if ($errorCode == '0') {//no errors
                $this->updateCategory($category->getId(), $json_result ['mvProductCategory'] ['ProductCategoryID']);
            } else {
                $entityId = $json_result['entityID'];//if category exists just sync them
                if (!empty($entityId) && $entityId > 0) {
                    if (strpos($json_result['ResponseStatus']['Message'], 'in the past and was deleted') !== false) {
                        $result = [
                                'mvCategoryId' => $json_result['entityID'],
                                'errorcode' => 'isdeleted'
                        ];
                        $undeleteUrl = $this->_backendUrl->getUrl('megaventory/index/undeleteEntity');
                        $this->_messageManager->addError('Category '.$name.' is flagged as deleted in Megaventory. Presse <a onclick="MegaventoryManager.undeleteEntity(\'' . $undeleteUrl .'\','.$result['mvCategoryId'].',\'category\')" href="javascript:void(0);">here</a> if you want to automatically undelete it');
                    } else {
                        $this->updateCategory($category->getId(), $entityId);
                    }
                }
            }
        } else {//we must also update all children
        
            $this->updateCategoriesRecursively($category);
        }
    }
    
    private function createCategoryName($category)
    {
        $path = $category->getPath();
        $name = '';
        $categoryIds = explode('/', $path);
        foreach ($categoryIds as $categoryId) {
            $pCategory = $this->_categoryLoader->create()->load($categoryId);
            $pName = $pCategory->getName();
            if (isset($pName) && $pName != null) {
                $name .= $pName.'/';
            }
        }
    
        $name = rtrim($name, "/");
    
        return $name;
    }
    
    public function importCategoriesToMegaventory($page = 1, $imported = 0)
    {
        $collection = $this->_categoryCollectionFactory->create()
        ->addAttributeToSelect('level');
        $collection->addAttributeToSelect('path');
        $collection->addAttributeToSelect('entity_id');
        $collection->addAttributeToSelect('name');
        $collection->getSelect()->order('level');
        $collection->setPageSize(30);
        $collection->setCurPage($page);
        $totalCollectionSize = $collection->getSize();
    
        $isLastPage = false;
        if ((int)($totalCollectionSize/30) == $page-1) {
            $isLastPage = true;
        }
    
        $total = $imported;
        foreach ($collection as $category) {
            try {
                $inserted = $this->insertSingleCategory($category);
                if ($inserted == 0 || $inserted == 1) { //no errors
                    $total++;
                    $message = $total.'/'.$totalCollectionSize;
                    $this->_mvHelper->sendProgress(21, $message, $page, 'categories', false);
                }
            } catch (\Exception $ex) {
                $event = [
                        'code' => 'Category Insert',
                        'result' => '',
                        'magento_id' => $category->getId(),
                        'return_entity' => '0',
                        'details' => $ex->getMessage(),
                        'data' => ''
                ];
                $this->_mvHelper->log($event);
            }
        }

        if ($isLastPage) {
            $message = $total.'/'.$totalCollectionSize.' categories imported'.$this->_registry->registry('tickImage');
            if ($total != $totalCollectionSize) {
                $dif = $totalCollectionSize-$total;
                $logUrl = $this->_backendUrl->getUrl("megaventory/index/log");
                if ($dif == 1) {
                    $message .= '<br>'.$dif.' category was not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.$this->_registry->registry('errorImage');
                } else {
                    $message .= '<br>'.$dif.' categories were not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.$this->_registry->registry('errorImage');
                }
            }

            $this->_mvHelper->sendProgress(21, $message, $page, 'categories', true);
                
            return false;
        } else {
            $result =
            [
                    'nextpage' => $page+1,
                    'imported' => $total
            ];
                
            return $result;
        }
    }
    
    public function insertSingleCategory($category)
    {
        $megaventoryId = '0';
        $descr = $category->getDescription();
        $name = $this->createCategoryName($category);
    
        //default magento sample data get a 'root' category
        //with no name. we insert it in Mv using the
        //the special [No Name] title
        if (empty($name)) {
            $name = '[No Name]';
        }
    
        if (isset($descr) && $descr != null) {
            $description = $descr;
        } else {
            $description = '';
        }
    
        $data =  [
                'APIKEY' => $this->APIKEY,
                'mvProductCategory' =>  [
                        'ProductCategoryID' => $megaventoryId,
                        'ProductCategoryName' => $name,
                        'ProductCategoryDescription' => $description ],
                'mvRecordAction' => 'Insert' ];
    
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'ProductCategoryUpdate', $category->getId());
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode == '0') {//no errors
            $this->updateCategory($category->getId(), $json_result ['mvProductCategory'] ['ProductCategoryID']);
        } else {
            $entityId = $json_result['entityID'];//if category exists just sync them
            if (!empty($entityId) && $entityId > 0) {
                if (strpos($json_result['ResponseStatus']['Message'], 'in the past and was deleted') !== false) {
                    $result = [
                            'mvCategoryId' => $json_result['entityID'],
                            'errorcode' => 'isdeleted'
                    ];
                    return $result;
                } else {
                    $this->updateCategory($category->getId(), $entityId);
                    return 1;
                }
            }
        }
    
        return $errorCode;
    }
    
    private function updateCategoriesRecursively($category)
    {
    
        $megaventoryCategoryId = $category->getData('mv_productcategory_id');
    
        if (isset($megaventoryCategoryId)) {
            $name = $this->createCategoryName($category);
            $descr = $category->getDescription();
    
            if (isset($descr) && $descr != null) {
                $description = $descr;
            } else {
                $description = '';
            }
    
            $data =  [
                    'APIKEY' => $this->APIKEY,
                    'mvProductCategory' =>  [
                            'ProductCategoryID' => $megaventoryCategoryId,
                            'ProductCategoryName' => $name,
                            'ProductCategoryDescription' => $description ],
                    'mvRecordAction' => 'Update' ];
    
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'ProductCategoryUpdate', $category->getEntityId());
    
            $errorCode = $json_result['ResponseStatus']['ErrorCode'];
            if ($errorCode != '0') {//no errors
                $entityId = $json_result['entityID'];//if category exists just sync them
                if (!empty($entityId) && $entityId > 0) {
                    if (strpos($json_result['ResponseStatus']['Message'], 'in the past and was deleted') !== false) {
                        $result = [
                                'mvCategoryId' => $json_result['entityID'],
                                'errorcode' => 'isdeleted'
                        ];
                        $undeleteUrl = $this->_backendUrl->getUrl('megaventory/index/undeleteEntity');
                        $this->_messageManager->addError('Category '.$name.' is flagged as deleted in Megaventory. Presse <a onclick="MegaventoryManager.undeleteEntity(\'' . $undeleteUrl .'\','.$result['mvCategoryId'].',\'category\')" href="javascript:void(0);">here</a> if you want to automatically undelete it');
                    }
                }
            }
        }
    
        $children = $category->getChildrenCategories();
        $hasChildren = $children && $children->count();
        if ($hasChildren) {
            foreach ($children as $tmpCategory) {
                $this->updateCategoriesRecursively($tmpCategory);
            }
        }
    }
    
    //TODO
    /* public function getChildrenCategories($category)
    {
        $collection = $category->getCollection();
        $collection->addAttributeToSelect('url_key')
        ->addAttributeToSelect('name')
        ->addAttributeToSelect('all_children')
        ->addAttributeToSelect('is_anchor')
        ->addAttributeToSelect('description')
        ->addAttributeToFilter('is_active', 1)
        ->addIdFilter($category->getChildren())
        ->setOrder('position', 'ASC')
        ->joinUrlRewrite()
        ->load();
        return $collection;
    } */
    
    public function deleteCategoriesRecursively($category)
    {
        $children = $category->getChildrenCategories();
        $hasChildren = $children && $children->count();
        if ($hasChildren) {
            foreach ($children as $tmpCategory) {
                $this->deleteCategoriesRecursively($tmpCategory);
            }
        }
    
        $megaventoryCategoryId = $category->getData('mv_productcategory_id');
    
        if (isset($megaventoryCategoryId)) {
            $data =  [
                    'APIKEY' => $this->APIKEY,
                    'ProductCategoryIDToDelete' => $megaventoryCategoryId,
                    'mvCategoryDeleteAction' => 'LeaveProductsOrphan'];
    
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'ProductCategoryDelete', $category->getEntityId());
        }
    }
    
    private function updateCategory($categoryId, $mvCategoryId)
    {
        $connection = $this->_resource->getConnection();
        $table = $this->_resource->getTableName('catalog_category_entity');
        $sql_insert = "update ".$table." set mv_productcategory_id = ".$mvCategoryId." where entity_id = ".$categoryId;
        $connection->query($sql_insert);
    }
}
