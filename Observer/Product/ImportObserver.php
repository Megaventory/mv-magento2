<?php
namespace Mv\Megaventory\Observer\Product;

use \Magento\Framework\Event\ObserverInterface;

class ImportObserver implements ObserverInterface
{
    private $_mvProductHelper;
    private $_messageManager;
    private $_productLoader;
    
    protected $_logger;
    
    public function __construct(
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_mvProductHelper = $mvProductHelper;
        $this->_messageManager = $messageManager;
        $this->_productLoader = $productLoader;
    
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $adapter = $observer->getAdapter();
    
        if ($adapter) {
            $newSku = $adapter->getNewSku();
            
            foreach ($newSku as $sku => $skuValues) {
                $productId = $skuValues['entity_id'];
                $product = $this->_productLoader->create()->load((int)$productId);

                if ($product->getId()) {
                    $sku = $product->getSku();
                    $megaventoryId = $product->getData('mv_product_id');
                    $startsWith = (\strpos($sku, 'bom_') === 0);
                    if ($startsWith && empty($megaventoryId)) {
                        return;
                    }
    
                    $this->_mvProductHelper->addProduct($product);
                }
            }
        }
    }
}
