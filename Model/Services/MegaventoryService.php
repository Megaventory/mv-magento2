<?php

namespace Mv\Megaventory\Model\Services;

class MegaventoryService
{
    const STOCK_CONSUME_LIMIT = 50;
    
    protected $_scopeConfig;
    protected $_mvHelper;
    protected $_commonHelper;
    protected $_productFactory;
    protected $_inventoriesFactory;
    protected $_inventoriesHelper;
    protected $_productStocksFactory;
    protected $_stockItemFactory;
    protected $_sourceStockItemCollection;
    protected $_sourceItemSave;
    protected $_sourceLowStockItemInterface;
    protected $_sourceStockItemInterface;
    
    protected $_orderApi;
    protected $_orderFactory;
    protected $_orderStatusHistoryFactory;
    protected $_convertOrder;
    protected $_mvOrderHelper;
    
    protected $_shipmentRepository;
    protected $_shipmentFactory;
    protected $_shipmentCommentFactory;
    protected $_shipmentSender;
    protected $_shipmentNotifier;
    protected $_trackFactory;
    
    protected $_invoiceService;
    protected $_invoiceRepository;
    
    protected $_transaction;
    
    protected $_logger;
    protected $mvLogFactory;
    protected $_resource;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Mv\Megaventory\Helper\Common $commonHelper,
        \Mv\Megaventory\Helper\Order $orderHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesFactory,
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Mv\Megaventory\Model\ProductstocksFactory $productStocksFactory,
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderApi,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceCollection,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemSave,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderStatusHistoryFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\InvoiceRepository $invoiceRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\CommentFactory $shipmentCommentFactory,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\DB\Transaction $transaction,
        \Mv\Megaventory\Model\LogFactory $mvLogFactory,
        \Mv\Megaventory\Logger\Logger $logger,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\InventoryLowQuantityNotificationApi\Api\GetSourceItemConfigurationInterface $sourceItemConfig,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceStockItemInterface
    ) {
        $this->_scopeConfig = $scopeConfig;
        
        $this->_mvHelper = $mvHelper;
        $this->_commonHelper = $commonHelper;
        $this->_productFactory = $productFactory;
        $this->_inventoriesFactory = $inventoriesFactory;
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_productStocksFactory = $productStocksFactory;
        $this->_stockItemFactory = $stockItemFactory;
        $this->_sourceStockItemCollection = $sourceCollection;
        $this->_sourceItemSave = $sourceItemSave;
        $this->_sourceLowStockItemInterface = $sourceItemConfig;

        $this->_sourceStockItemInterface = $sourceStockItemInterface;
        
        $this->_orderApi = $orderApi;
        $this->_orderFactory = $orderFactory;
        $this->_orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->_convertOrder = $convertOrder;
        $this->_mvOrderHelper = $orderHelper;
        
        $this->_shipmentRepository = $shipmentRepository;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_shipmentCommentFactory = $shipmentCommentFactory;
        $this->_shipmentSender = $shipmentSender;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_trackFactory = $trackFactory;
        
        $this->_invoiceService = $invoiceService;
        $this->_invoiceRepository = $invoiceRepository;
        
        $this->_transaction = $transaction;
        
        $this->_mvLogFactory = $mvLogFactory;
        $this->_logger = $logger;
        $this->_resource = $resource;
    }
    
    public function applyPendingUpdates()
    {
        $this->_logger->info('update run');
    
        $key = $this->_scopeConfig->getValue('megaventory/general/apikey');
    
        $magentoId = $this->_scopeConfig->getValue('megaventory/general/magentoid');
    
        if (!isset($magentoId)) {
            $magentoId = "magento";
        }
            
        $data =
        [
                'APIKEY' => $key,
                'Filters' => [
                                "AndOr" => "And",
                                "FieldName" => "Application",
                                "SearchOperator" => "Equals",
                                "SearchValue" => $magentoId
                             ]
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'IntegrationUpdateGet', 0);
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            $this->_logger->info('error');
            $this->_logger->info($errorCode);
            $this->_logger->info($json_result['ResponseStatus']['Message']);
            return;
        }
    
        $mvIntegrationUpdates = $json_result['mvIntegrationUpdates'];
    
        foreach ($mvIntegrationUpdates as $mvIntegrationUpdate) {
            $result = false;
                
            $this->_logger->info('integration update id : '.$mvIntegrationUpdate['IntegrationUpdateID']);
            $entityIDs = explode('##$', $mvIntegrationUpdate['EntityIDs']);
                
            $mvIntegrationUpdateId = $mvIntegrationUpdate['IntegrationUpdateID'];
            $tries = $mvIntegrationUpdate['Tries'];
    
            //delete if failed more than 10 times
            if ($tries > 10) {
                $this->deleteUpdate($mvIntegrationUpdateId);
            }
    
            if ($mvIntegrationUpdate['Entity'] == 'product') {
                $product = json_decode($mvIntegrationUpdate['JsonData'], true);
    
                if ($mvIntegrationUpdate['Action'] == 'update') {
                } elseif ($mvIntegrationUpdate['Action'] == 'delete') {
                } elseif ($mvIntegrationUpdate['Action'] == 'insert') {
                }
            } elseif ($mvIntegrationUpdate['Entity'] == 'sales_order') {
                try {
                    for ($i = 0; $i < count($entityIDs); $i++) {
                        $orderIncrementId = $entityIDs[$i];
                        $order = $this->_orderFactory->create()->load($orderIncrementId, 'increment_id');
                            
                        if ($mvIntegrationUpdate['Action'] == 'complete') {
                            $orderStatusHistory = $this->_orderStatusHistoryFactory->create();
                            $orderStatusHistory->setStatus('complete');
                            $orderStatusHistory->setComment('');
                            $orderStatusHistory->setIsCustomerNotified(false);
                            $result = $this->_orderApi->addComment($order->getId(), $orderStatusHistory);
                                
                            if ($result) {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            } else {
                                if ($tries > 10) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                } else {
                                    $mvIntegrationUpdate['Tries'] = $tries+1;
                                    $mvIntegrationUpdate['payload'] = $result;
                                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                }
                            }
                        } elseif ($mvIntegrationUpdate['Action'] == 'cancel') {
                            $result = $this->_orderApi->cancel($order->getId());
                                
                            if ($result) {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            } else {
                                if ($tries > 10) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                } else {
                                    $mvIntegrationUpdate['Tries'] = $tries+1;
                                    $mvIntegrationUpdate['payload'] = $result;
                                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                }
                            }
                        } elseif ($mvIntegrationUpdate['Action'] == 'invoice') {
                            if ($order->canInvoice()) {
                                $newInvoice = $this->_invoiceService->prepareInvoice($order);
                                
                                $newInvoice->register();
                                
                                $newInvoice->getOrder()->setCustomerNoteNotify(false);
                                $newInvoice->getOrder()->setIsInProcess(true);
                    
                                $this->_transaction
                                    ->addObject($newInvoice)
                                    ->addObject($newInvoice->getOrder());
                                
                                $this->_transaction->save();
                                    
                                $invoiceIncrementId = $newInvoice->getIncrementId();
        
                                if ($newInvoice->canCapture()) {
                                    $newInvoice->capture();
                                }
        
                                $result = $invoiceIncrementId;
                                if ($result) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                } else {
                                    if ($tries > 10) {
                                        $this->deleteUpdate($mvIntegrationUpdateId);
                                    } else {
                                        $mvIntegrationUpdate['Tries'] = $tries+1;
                                        $mvIntegrationUpdate['payload'] = $result;
                                        $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                    }
                                }
                            } else {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            }
                        } elseif ($mvIntegrationUpdate['Action'] == 'ship') {
                            $jsonData = $mvIntegrationUpdate['JsonData'];
                            $extraShippingInformation = json_decode($jsonData, true);
                            
                            if ($order->canShip()) {
                                $newShipment = $this->_shipmentFactory->create($order, []);
                                
                                // Loop through order items
                                foreach ($order->getAllItems() as $orderItem) {
                                    // Check if order item has qty to ship or is virtual
                                    if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                                        continue;
                                    }
                                    
                                    $qtyShipped = $orderItem->getQtyToShip();
                                    
                                    // Create shipment item with qty
                                    $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)
                                    ->setQty($qtyShipped);
                                    
                                    // Add shipment item to shipment
                                    $newShipment->addItem($shipmentItem);
                                }

                                $newShipment->register();
                                $newShipment->getOrder()->setIsInProcess(true);

                                $sourceCode = $this->_inventoriesFactory->create()
                                ->load($order->getMvInventoryId())->getStockSourceCode();

                                $newShipment->getExtensionAttributes()->setSourceCode($sourceCode);
                                $error = false;

                                try {
                                    $this->_transaction
                                    ->addObject($newShipment)
                                    ->addObject($newShipment->getOrder())
                                    ->save();
                                } catch (\Exception $e) {
                                    $error = true;
                                    $this->_logger->debug('Exception message: '.$e->getMessage());
                                }
                                
                                if (!$error) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                    if ($extraShippingInformation['Notify'] == '1') {//then also send a shipment email
                                        $this->_shipmentSender->send($newShipment);
                                    }
                                } else {
                                    if ($tries > 10) {
                                        $this->deleteUpdate($mvIntegrationUpdateId);
                                    } else {
                                        $mvIntegrationUpdate['Tries'] = $tries+1;
                                        $mvIntegrationUpdate['payload'] = $result;
                                        $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                    }
                                }
                            } else {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            }
                        } elseif ($mvIntegrationUpdate['Action'] == 'track') {
                            $jsonData = $mvIntegrationUpdate['JsonData'];
    
                            $result = false;
    
                            $shipmentIncrementId= "";
                                
                            $orderId = $order->getId();
    
                            $orderFilter = new \Magento\Framework\Api\Filter();
                            $orderFilter->setField('order_id');
                            $orderFilter->setValue($orderId);
                            $orderFilter->setConditionType('eq');
                            
                            $filterGroup = new \Magento\Framework\Api\Search\FilterGroup();
                            $filterGroup->setFilters([$orderFilter]);
                            
                            $searchCriteria = new \Magento\Framework\Api\SearchCriteria();
                            $searchCriteria->setFilterGroups([$filterGroup]);
                            $shipmentResult = $this->_shipmentRepository->getList($searchCriteria);
                            
                            if ($shipmentResult->getTotalCount() > 0) {
                                $shipment = current($shipmentResult->getItems());
                                $shipmentIncrementId = $shipment->getIncrementId();
                            }
    
                            if ($jsonData && !empty($shipmentIncrementId)) {
                                $trackingInformation = json_decode($jsonData, true);
                                $result = $this->megaventoryAddTrack(
                                    $shipment,
                                    'custom',
                                    $trackingInformation['ShippingProviderName'],
                                    $trackingInformation['TrackNumber'],
                                    $trackingInformation['Notify']
                                );
                            }

                            if ($result) {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            } else {
                                if ($tries > 10) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                } else {
                                    $mvIntegrationUpdate['Tries'] = $tries+1;
                                    $mvIntegrationUpdate['payload'] = $result;
                                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                }
                            }
                        } elseif ($mvIntegrationUpdate['Action'] == 'partially_process') {
                            $orderStatusHistory = $this->_orderStatusHistoryFactory->create();
                            $orderStatusHistory->setStatus('processing');
                            $orderStatusHistory->setComment('');
                            $orderStatusHistory->setIsCustomerNotified(false);
                            $result = $this->_orderApi->addComment($order->getId(), $orderStatusHistory);
                                
                            if ($result) {
                                $this->deleteUpdate($mvIntegrationUpdateId);
                            } else {
                                if ($tries > 10) {
                                    $this->deleteUpdate($mvIntegrationUpdateId);
                                } else {
                                    $mvIntegrationUpdate['Tries'] = $tries+1;
                                    $mvIntegrationUpdate['payload'] = $result;
                                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->_logger->debug('exception message: '.$e->getMessage());
                    $mvIntegrationUpdate['Tries'] = $tries+1;
                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                }
            } elseif ($mvIntegrationUpdate['Entity'] == 'stock') {
                try {
                    $inventoryValues = json_decode($mvIntegrationUpdate['JsonData']);
                    $inventoryValues = array_filter($inventoryValues); //remove nulls

                    if (count($entityIDs) > MegaventoryService::STOCK_CONSUME_LIMIT) {
                        $newEntityIds = array_slice($entityIDs, 0, MegaventoryService::STOCK_CONSUME_LIMIT);
                        $remainingEntityIds = array_slice($entityIDs, MegaventoryService::STOCK_CONSUME_LIMIT);
                        $newInventoryValues = array_slice($inventoryValues, 0, MegaventoryService::STOCK_CONSUME_LIMIT);
                        $remainingInventoryValues = array_slice($inventoryValues, MegaventoryService::STOCK_CONSUME_LIMIT);
                    
                        $result = $this->updateMegaventoryStock($newEntityIds, $newInventoryValues, $mvIntegrationUpdateId);
                    
                        if ($result) {
                            $remainingEntityIds = implode('##$', $remainingEntityIds);
                            $mvIntegrationUpdate['EntityIDs'] = $remainingEntityIds;
                            $mvIntegrationUpdate['JsonData'] = json_encode($remainingInventoryValues);
                            $this->updateIntegrationUpdate($mvIntegrationUpdate);
                        }

                        continue;
                    }
                    
                    $result = $this->updateMegaventoryStock($entityIDs, $inventoryValues, $mvIntegrationUpdateId);
                        
                    if ($result) {
                        $this->deleteUpdate($mvIntegrationUpdateId);
                    } else {
                        $mvIntegrationUpdate['Tries'] = $tries+1;
                        $mvIntegrationUpdate['payload'] = $result;
                        $this->updateIntegrationUpdate($mvIntegrationUpdate);
                    }
                } catch (\Exception $e) {
                    $this->_logger->debug('exception message: '.$e->getMessage());
                    $mvIntegrationUpdate['Tries'] = $tries+1;
                    $this->updateIntegrationUpdate($mvIntegrationUpdate);
                }
            } else { //NOT HANDLED UPDATE SHOULD BE DELETED
                try {
                    $this->deleteUpdate($mvIntegrationUpdateId);
                } catch (\Exception $e) {
                    $this->_logger->debug('exception message: '.$e->getMessage());
                }
            }
        }
    }
    
    private function deleteUpdate($mvIntegrationUpdateId)
    {
    
        $key = $this->_scopeConfig->getValue('megaventory/general/apikey');
    
        $data =
        [
                'APIKEY' => $key,
                'IntegrationUpdateIDToDelete' => $mvIntegrationUpdateId
        ];
    
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'IntegrationUpdateDelete', 0);
    }
    
    private function updateIntegrationUpdate($mvIntegrationUpdate)
    {
    
        $key = $this->_scopeConfig->getValue('megaventory/general/apikey');
    
        $data =
        [
                'APIKEY' => $key,
                'mvIntegrationUpdate' => $mvIntegrationUpdate,
                'mvRecordAction' => 'Update'
        ];
    
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'IntegrationUpdateUpdate', 0);
    }
    
    public function updateMegaventoryStock($productSKUs, $inventoryValues, $integratiodId = 0)
    {
        $configValue = $this->_scopeConfig->getValue('cataloginventory/options/can_subtract');

        if (! $this->_commonHelper->isMegaventoryEnabled()) {
            return false;
        }
    
        if (count($productSKUs) != count($inventoryValues)) {
            return false;
        }
    
        $inventoryValues = ( array ) $inventoryValues;
        $json = json_encode($inventoryValues);
    
        $inventoryValues = json_decode($json, true);
        $productIds =  [];
        foreach ($productSKUs as $index => $productSKU) {
            if (empty($productSKU)) {
                continue;
            }
            
            $megaventoryId = $inventoryValues [$index] ['inventory_id'];
            $stockData = $inventoryValues [$index] ['stock_data'];
            
            $productId = $this->_productFactory->create()->getIdBySku($productSKU);
            
            if (!is_array($stockData)) {
                continue;
            }
            
            $stockKeys = array_keys($stockData);
                
            if ($productId) {
                $inventory = $this->_inventoriesHelper->getInventoryFromMegaventoryId($megaventoryId);
    
                if ($inventory != false) {
                    $this->_inventoriesHelper->updateInventoryProductStock($productId, $inventory->getId(), $stockData, false, $integratiodId);
                    
                    $productIds [] = $productId;
                }
            }
        }
    
        foreach ($productIds as $pId) {
            $productStockCollection = $this->_productStocksFactory->create()->loadProductstocks($pId);

            $sku = $this->_productFactory->create()->load($pId)->getSku();

            $stockItem = $this->_stockItemFactory->create()->load($pId, 'product_id');

            // Remove reserved quantities because it doubles the quantity removed from the saleable quantity of the product.
            $connection = $this->_resource->getConnection();
            $tableName = $this->_resource->getTableName('inventory_reservation');
            
            if (! $connection->isTableExists($tableName)) {
                continue;
            }
            $connection->delete($tableName, ['sku = ?'=>$sku]);
            
            $totalAlertQuantity = 0;
            foreach ($productStockCollection as $key => $productStock) {
                $inventoryStock = $productStock ['stockqty'];
                $inventoryNonShippedStock = $productStock ['stocknonshippedqty'];
                $inventoryNonAllocatedWOStock = $productStock ['stocknonallocatedwoqty'];
                $inventoryAlertQty = $productStock ['stockalarmqty'];
    
                $inventoryId = $productStock ['inventory_id'];
                
                $inventory = $this->_inventoriesFactory->create()->load($inventoryId);
                if ($inventory == false) {
                    continue;
                }

                if (is_null($inventory->getStockSourceCode())) {
                    continue;
                }
    
                $sourceItems = $this->_sourceStockItemCollection->create()
                ->addFieldToFilter('source_code', $inventory->getStockSourceCode())
                ->addFieldToFilter('sku', $sku);

                if (count($sourceItems) > 0) {
                    $sourceItem = $sourceItems->getFirstItem();
                    $notifyQty = $this->_sourceLowStockItemInterface->execute($inventory->getStockSourceCode(), $sku)
                    ->getNotifyStockQty();
                } else {
                    $sourceItem = $this->_sourceStockItemInterface->create();
                    $sourceItem->setSku($sku);
                    $sourceItem->setSourceCode($inventory->getStockSourceCode());
                    $notifyQty = 1;
                }

                $qty = 0;
                if ($configValue == '0') { //no decrease value when order is placed
                    $qty = $inventoryStock;
                } else {//decrease stock when order is placed
                
                    $qty = $inventoryStock-$inventoryNonShippedStock-$inventoryNonAllocatedWOStock;
                }

                $qty = ($qty > 0) ? $qty : 0;

                $isInStock = ($qty > $notifyQty) ? 1 : 0;
                $sourceItem->setQuantity($qty);
                $sourceItem->setStatus($isInStock);

                $this->_sourceItemSave->execute([$sourceItem]);
                    
                $totalAlertQuantity += $inventoryAlertQty;
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
            //end of notify quantity
                
            $stockItem->save();
        }
    
        return true;
    }
    
    public function megaventoryAddTrack(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $carrier,
        $title,
        $trackNumber,
        $notify
    ) {
        if ($shipment) {
            $track = $this->_trackFactory->create();
            
            $track
                ->setNumber($trackNumber)
                ->setCarrierCode($carrier)
                ->setTitle($title);
            
            $shipment->addTrack($track);
            $shipment->save();
            $track->save();
            
            if ($notify == 1) {
                $this->_shipmentNotifier->notify($shipment);
                $shipment->save();
            }
        }
        
        return $track->getId();
    }
}
