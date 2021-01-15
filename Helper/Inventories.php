<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Inventories extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;
    protected $_sourceStockItemInterface;
    protected $_sourceItemSave;
    private $_mvHelper;
    private $_productstocksLoader;
    private $_inventoriesLoader;
    protected $_sourceLowStockItemInterface;
    private $_inventoriesCollection;
    private $_resource;
    protected $_stockItemFactory;
    protected $_productFactory;
    private $_messageManager;
    private $_backendUrl;
    protected $_sourceItemCollectionFactory;
    private $APIKEY;
    
    protected $logger;
    protected $mvLogFactory;
    
    const PAGESIZE  = 50;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $mvHelper,
        \Mv\Megaventory\Model\ProductstocksFactory $productStocksLoader,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Model\ResourceModel\Inventories\Collection $inventoriesCollection,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceStockItemInterface,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemSave,
        LogFactory $mvLogFactory,
        Logger $logger,
        \Magento\InventoryLowQuantityNotificationApi\Api\GetSourceItemConfigurationInterface $sourceItemConfig
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_mvHelper = $mvHelper;
        $this->_sourceStockItemInterface = $sourceStockItemInterface;
        $this->_productstocksLoader = $productStocksLoader;
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_inventoriesCollection = $inventoriesCollection;
        $this->_sourceItemSave = $sourceItemSave;
        $this->_resource = $recource;
        $this->_stockItemFactory = $stockItemFactory;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
        $this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
        $this->_sourceItemCollectionFactory = $sourceCollection;
        $this->_productFactory = $productFactory;
        $this->_sourceLowStockItemInterface = $sourceItemConfig;
                
        $this->mvLogFactory = $mvLogFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }
    
    public function getInventories()
    {
        return $this->_inventoriesCollection->load();
    }

    public function assignLocationToSource($inventory, $source) {
        $codeAlreadyAssigned = (
                count(
                    $this->_inventoriesCollection->addFieldToFilter('stock_source_code', $source->getSourceCode())
                    ->addFieldToFilter('id', ['neq'=>$inventory->getId()])->load()
                ) > 0
            );
        if ($codeAlreadyAssigned) {
            return [
                'status'=>'error',
                'message'=>'Magento Inventory Source ' . $source->getName() . ' is already assigned to the Megaventory Location '.$inventory->getName() . '. If you are to proceed, please reassign this location to another source.'
            ];
        } else {
            $inventory = $this->_inventoriesLoader->create()->load($inventory->getId());
            $inventory->setStockSourceCode($source->getSourceCode());
            $inventory->setCountsInGlobalStock(1);
            $inventory->save();
            return [
                'status'=>'success',
                'message'=>'Magento Inventory Source ' . $source->getName() . ' has been successfully assigned to Megaventory Location '.$inventory->getName() . '.'
            ];
        }
    }

    public function getInventoriesFromMegaventory($apikey = false, $apiurl = false, $enabled = -1)
    {
        if ($apikey  != false) {
            $key = $apikey;
        } else {
            $key = $this->APIKEY;
        }
    
        $data =
        [
                'APIKEY' => $key
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationGet', 0, $apiurl, $enabled);
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return false;
        }
    
        try {
            $mvInventoryLocations = $json_result['mvInventoryLocations'];
        } catch (\Exception $ex) {
            return false;
        }
    
        return count($mvInventoryLocations);
    }
    
    public function getInventoryFromMegaventoryId($mvInventoryId)
    {
        $inventory = $this->_inventoriesLoader->create()->load($mvInventoryId, 'megaventory_id');

        if ($inventory->getId() == false) {
            return false;
        }
        
        return $inventory;
    }

    public function syncrhonizeInventories($page, $apikey = -1, $apiurl = -1)
    {
        if ($apikey  != -1) {
            $key = $apikey;
        } else {
            $key = $this->APIKEY;
        }
            
        $data =
        [
                'APIKEY' => $key
        ];
                
        try {
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationGet', 0, $apiurl);
        } catch (\Exception $ex) {
            return false;
        }
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return false;
        }
    
        $mvInventoryLocations = $json_result['mvInventoryLocations'];
    
        $i = 0;
        $result = -1;
        foreach ($mvInventoryLocations as $mvInventory) {
            $inventory = $this->checkIfInventoryExists($mvInventory);
            if ($inventory == false) {
                $this->insertInventory($mvInventory);
            } else {
                $this->updateInventory($inventory, $mvInventory);
            }
                
            $mvIds[] = $mvInventory['InventoryLocationID'];
            $i++;
        }
    
        if (count($mvIds) > 0) {
            $this->deleteNotExistentInventories($mvIds);
        }
        
        $nextPage = $this->updateAllStock($page);
    
        return $nextPage;
    }

    public function truncateReservationsTable()
    {
        $connection = $this->_resource->getConnection();
        $table = $this->_resource->getTableName('inventory_reservation');
        $connection->truncateTable($table);
    }
    public function updateAllStock($page)
    {
        $nextPage = -1;
        
        $data =
        [
                'APIKEY' => $this->APIKEY
        ];
        
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationStockGet', 0);
            
        if ($json_result != false) {
            $productStockList = $json_result['mvProductStockList'];
            $inventoryStockData = [];
            $configValue = $this->_scopeConfig->getValue('cataloginventory/options/can_subtract');
            $allProducts = count($productStockList);
            $allPages = (int)($allProducts/Inventories::PAGESIZE) + 1;
                
            if ($page == $allPages) {
                $nextPage = -1;
            } else {
                $nextPage = $page + 1;
            }
                
            $i = 0;
            foreach ($productStockList as $productStockListItem) {
                if ($i < ($page-1)*Inventories::PAGESIZE) {
                    $i++;
                    continue;
                }

                if ($i >= $page*Inventories::PAGESIZE) {
                    break;
                }
                
                $i++;
                
                $pId = $this->getIdByMegaventoryId($productStockListItem['productID']);
                $sku = $this->_productFactory->create()->load($pId)->getSku();
                
                if (empty($pId) || $pId == false) {
                    continue;
                }
                    
                $warehouseStocks = $productStockListItem['mvStock'];
                foreach ($warehouseStocks as $warehouseStock) {
                    $inventoryStockData['stockqty'] = $warehouseStock['StockPhysical'];
                    $inventoryStockData['stockqtyonhold']= $warehouseStock['StockOnHold'];
                    $inventoryStockData['stocknonshippedqty'] = $warehouseStock['StockNonShipped'];
                    $inventoryStockData['stocknonallocatedwoqty'] = $warehouseStock['StockNonAllocatedWOs'];
                    $inventoryStockData['stocknonreceivedqty'] = $warehouseStock['StockNonReceivedPOs'];
                    $inventoryStockData['stockwipcomponentqty'] = 0;
                    $inventoryStockData['stocknonreceivedwoqty'] = $warehouseStock['StockNonReceivedWOs'];
                    $inventoryStockData['stockalarmqty'] = $warehouseStock['StockAlertLevel'];
                        
                    //warehouseID changed in megaventory API v2
                    //we need to be compliant with both versions
                    
                    if (!in_array('warehouseID', array_keys($warehouseStock))) {
                        $locationId = $warehouseStock['InventoryLocationID'];
                    } else {
                        $locationId = $warehouseStock['warehouseID'];
                    }

                    $inventory = $this->_inventoriesLoader->create()->load($locationId, 'megaventory_id');
                    
                    $inventoryId = $inventory->getData('id');
                    
                    $this->updateInventoryProductStock($pId, $inventoryId, $inventoryStockData);
                        
                    $sourceCode = $inventory->getStockSourceCode();
                    if ($sourceCode !== null) {
                        $sourceItems = $this->_sourceItemCollectionFactory->create()
                        ->addFieldToFilter('source_code', $sourceCode)
                        ->addFieldToFilter('sku', $sku);
                        if (count($sourceItems) > 0) {
                            $sourceItem = $sourceItems->getFirstItem();
                            $notifyQty = $this->_sourceLowStockItemInterface->execute($sourceCode, $sku)
                            ->getNotifyStockQty();
                        } else {
                            $sourceItem = $this->_sourceStockItemInterface->create();
                            $sourceItem->setSku($sku);
                            $sourceItem->setSourceCode($sourceCode);
                            $notifyQty = 1;
                        }

                        $qty = 0;
                        if ($configValue == '0') { //no decrease value when order is placed
                            $qty = $inventoryStockData['stockqty'];
                        } else { //decrease stock when order is placed
                        
                            $stock = $inventoryStockData['stockqty'];
                            $nonShipped = $inventoryStockData['stocknonshippedqty'];
                            $allocated = $inventoryStockData['stocknonallocatedwoqty'] + $nonShipped;
                            $qty = $stock - $allocated;
                        }

                        $isInStock = ($qty > $notifyQty) ? 1 : 0;
                        $sourceItem->setQuantity($qty);
                        $sourceItem->setStatus($isInStock);

                        $this->_sourceItemSave->execute([$sourceItem]);
                    }
                }
            }
        }
        
        return $nextPage;
    }
    
    public function initializeInventoryLocations($apikey = -1, $apiurl = -1)
    {
        if ($apikey  != -1) {
            $key = $apikey;
        } else {
            $key = $this->APIKEY;
        }
            
        $data =
        [
                'APIKEY' => $key
        ];
    
        try {
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationGet', 0, $apiurl);
        } catch (\Exception $ex) {
            return -1;
        }
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return -1;
        }
    
        $mvInventoryLocations = $json_result['mvInventoryLocations'];
    
        $i = 0;
        $result = -1;
        $mvIds = [];
        foreach ($mvInventoryLocations as $mvInventory) {
            $this->insertInventory($mvInventory);
            $i++;
        }
    
        if ($i>0) {
            return $i;
        }
    
        return $result;
    }
    
    public function createMainInventory()
    {
        $data =
        [
                'APIKEY' => $this->APIKEY,
                'mvInventoryLocation' => [
                        'InventoryLocationID' => '0',
                        'InventoryLocationName' => 'Main Inventory',
                        'InventoryLocationAbbreviation' => 'Main',
                        'InventoryLocationAddress' => '',
                        'InventoryLocationCurrencyCode' => ''
                ],
                'mvRecordAction' => 'Insert'
        ];
    
        try {
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationUpdate', 0);
        } catch (\Exception $ex) {
            return 'There was a problem connecting to your Megaventory account. Please try again.';
        }
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return $json_result['ResponseStatus']['Message'];
        }
    
        $this->insertInventory($json_result['mvInventoryLocation'], true);
    
        return true;
    }
    
    public function updateInventoryProductStock(
        $productId,
        $inventoryId,
        $stockData,
        $parentId = false,
        $integrationId = 0
    ) {
    
        if (isset($productId) && isset($inventoryId)) {
            $productStock = $this->_productstocksLoader->create()
            ->loadInventoryProductstock($inventoryId, $productId);
            $lastUpdateIntegrationId = (int)$productStock->getData('extra1');

            $this->logger->info('last update id = '.$lastUpdateIntegrationId);
            $this->logger->info('new update id = '.$integrationId);
            
            if ($lastUpdateIntegrationId <= $integrationId) {
                $productStock->setProduct_id($productId);
                $productStock->setInventory_id($inventoryId);
                $productStock->setStockqty($stockData['stockqty']);
                $productStock->setStockqtyonhold($stockData['stockqtyonhold']);
                $productStock->setStockalarmqty($stockData['stockalarmqty']);
                $productStock->setStocknonshippedqty($stockData['stocknonshippedqty']);
                $productStock->setStocknonreceivedqty($stockData['stocknonreceivedqty']);
                $productStock->setStockwipcomponentqty($stockData['stockwipcomponentqty']);
                $productStock->setStocknonreceivedwoqty($stockData['stocknonreceivedwoqty']);
                $productStock->setStocknonallocatedwoqty($stockData['stocknonallocatedwoqty']);
                if ($parentId != false) {
                    $productStock->setParent_id($parentId);
                }

                $productStock->setData('extra1', $integrationId);
                
                $productStock->save();
            }
            
            return true;
        }
    
        return false;
    }
    
    public function updateInventoryLocations($apikey = -1, $apiurl = -1)
    {
        if ($apikey  != -1) {
            $key = $apikey;
        } else {
            $key = $this->APIKEY;
        }
            
        $data =
        [
                'APIKEY' => $key
        ];
    
        try {
            $json_result = $this->_mvHelper->makeJsonRequest($data, 'InventoryLocationGet', 0, $apiurl);
        } catch (\Exception $ex) {
            return -1;
        }
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return -1;
        }
        
        $mvInventoryLocations = $json_result['mvInventoryLocations'];
    
        $i = 0;
        $result = -1;
        $mvIds = [];
        foreach ($mvInventoryLocations as $mvInventory) {
            $inventory = $this->checkIfInventoryExists($mvInventory);
            if ($inventory == false) {
                $this->insertInventory($mvInventory);
            } else {
                $this->updateInventory($inventory, $mvInventory);
            }
                
            $mvIds[] = $mvInventory['InventoryLocationID'];
            $i++;
        }
    
        if (count($mvIds) > 0) {
            $this->deleteNotExistentInventories($mvIds);
        }
    
        if ($i>0) {
            return $i;
        }
    
        return $result;
    }

    public function updateCountsInStock($inventoryId, $bCount)
    {
        $inventory = $this->_inventoriesLoader->create()->load($inventoryId);
        $bCount == 'true' ? $countsInStock = '1' : $countsInStock = '0';
        $inventory->setCounts_in_total_stock($countsInStock);
        $inventory->save();
    }
    
    public function updateInventoryProductAlertValue($productId, $inventoryId, $alertValue)
    {
    
        if (isset($productId) && isset($inventoryId)) {
            $productStock = $this->_productstocksLoader->create()
            ->loadInventoryProductstock($inventoryId, $productId);
                
            $productStock->setProduct_id($productId);
            $productStock->setInventory_id($inventoryId);
            $productStock->setStockalarmqty($alertValue);
            $productStock->save();
                
            $stockItem = $this->_stockItemFactory->create()->load($productId, 'product_id');
                
            $productStockCollection = $this->_productstocksLoader->create()->loadProductstocks($productId);
                
            $totalAlertQuantity = 0;
            foreach ($productStockCollection as $key => $productStock) {
                $inventoryAlertQty = $productStock ['stockalarmqty'];
                    
                $inventory = $this->_inventoriesLoader->create()->load($inventoryId);
                if ($inventory == false) {
                    continue;
                }
                    
                if ($inventory->getCounts_in_total_stock() == '1') {
                    $totalAlertQuantity += $inventoryAlertQty;
                }
            }
                
            //update notify quantity
            $useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
            $configValue = $this->_scopeConfig->getValue('cataloginventory/item_options/notify_stock_qty');
                
            if ($useConfigNotify == '1') {
                if (isset($configValue)) {
                    if ($configValue != $totalAlertQuantity) {
                        $stockItem->setData('use_config_notify_stock_qty', 0);
                    }
                }
            } else {
                if (isset($configValue)) {
                    if ($configValue == $totalAlertQuantity) {
                        $stockItem->setData('use_config_notify_stock_qty', 1);
                    }
                }
            }
                
            $stockItem->setData('notify_stock_qty', $totalAlertQuantity);
            $stockItem->save();
            //end of notify quantity
                
            return [
                    'totalAlertQuantity' => $totalAlertQuantity,
                    'isConfig' => ($totalAlertQuantity == $configValue) ? true : false
            ];
        }
    
        return [
                'totalAlertQuantity' => 0,
                'isConfig' => false
        ];
    }
    
    private function checkIfInventoryExists($mvInventory)
    {
    
        $inventory = $this->_inventoriesLoader->create()->load($mvInventory['InventoryLocationID'], 'megaventory_id');
        
        $id = $inventory->getData('id');
        if (!isset($id)) {
            return false;
        }
    
        return $inventory;
    }
    
    private function insertInventory($mvInventory, $default = false)
    {
        $mvInventoryLocationID = $mvInventory['InventoryLocationID'];
        $mvInventoryLocationName = $mvInventory['InventoryLocationName'];
        $mvInventoryLocationAbbreviation = $mvInventory['InventoryLocationAbbreviation'];
        $mvInventoryLocationAddress = $mvInventory['InventoryLocationAddress'];
        
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('megaventory_inventories');
        
        if ($default == false) {
            $sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, counts_in_total_stock) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","0")';
        } else {
            $sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, stock_source_code) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","default")';
        }
        
        $connection->query($sql_insert);
    }
    
    private function updateInventory($inventory, $mvInventory)
    {
        $inventory->setData('shortname', $mvInventory['InventoryLocationAbbreviation']);
        $inventory->setData('name', $mvInventory['InventoryLocationName']);
        $inventory->setData('address', $mvInventory['InventoryLocationAddress']);
        $inventory->setData('InventoryLocationAddress', $mvInventory['InventoryLocationAddress']);
        $inventory->save();
    }
    
    private function deleteNotExistentInventories($mvIds)
    {
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('megaventory_inventories');
        
        $sqlDelete = 'delete from '.$tableName.' where megaventory_id not in ('.implode(',', $mvIds).')';
        $connection->query($sqlDelete);
    }
    
    public function getIdByMegaventoryId($mvId)
    {
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('catalog_product_entity');
        
        $productId = $connection->fetchOne('select entity_id from '.$tableName.' where mv_product_id = '.$mvId);
        
        return $productId;
    }
}
