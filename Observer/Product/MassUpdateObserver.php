<?php

namespace Mv\Megaventory\Observer\Product;

use \Magento\Framework\Event\ObserverInterface;

class MassUpdateObserver implements ObserverInterface
{
    private $_mvProductHelper;
    private $_messageManager;
    private $_productLoader;
    protected $_logger;
    
    public function __construct(
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \Psr\Log\LoggerInterface $logger // log injection
    ) {
        $this->_mvProductHelper = $mvProductHelper;
        $this->_messageManager = $messageManager;
        $this->_productLoader = $productLoader;
        
        $this->_logger = $logger;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $indexModelEvent = $event->getObject();
        $entityType = $indexModelEvent->getData('entity');
        $actionType = $indexModelEvent->getData('type');
        
        if ($entityType == 'catalog_product' && $actionType == 'mass_action') {
            $dataObject = $indexModelEvent->getData('data_object');
            if ($dataObject) {
                $productIds = $dataObject->getData('product_ids');
                if (isset($productIds) && count($productIds) > 0) {
                    foreach ($productIds as $productId) {
                        $product = $this->_productLoader->create()->load($productId);
                        $this->_mvProductHelper->addProduct($product);
                    }
                }
            }
        }
    }
}
