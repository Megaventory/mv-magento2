<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Cron;

class CheckOrderSynchronization
{

    protected $_scopeConfig;
    protected $_orderCollectionFactory;
    protected $_orderLoader;
    protected $_quoteLoader;
    protected $_websiteRepository;
    protected $_mvOrderHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $orderLoader,
        \Magento\Quote\Model\QuoteFactory $quoteLoader,
        \Magento\Store\Model\WebsiteRepository $websiteRepository,
        \Mv\Megaventory\Helper\Order $mvOrderHelper
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderLoader = $orderLoader;
        $this->_quoteLoader = $quoteLoader;
        $this->_websiteRepository = $websiteRepository;
        $this->_mvOrderHelper = $mvOrderHelper;
    }

    public function execute()
    {
        $orderSynchronization = $this->_scopeConfig->getValue('megaventory/general/ordersynchronization');
        if (!empty($orderSynchronization) && ($orderSynchronization != '0')) {

            /* Format our dates */
            $fromDate = date('Y-m-d H:i:s', strtotime("-30 minutes"));
            $toDate = date('Y-m-d H:i:s', strtotime("now"));
            
            /* Get the collection */
            $orders = $this->_orderCollectionFactory->create()
            ->addAttributeToFilter('created_at', ['from'=>$fromDate, 'to'=>$toDate])
            ->addAttributeToFilter('status', ['in' => ['pending','processing']])
            ->load();

            foreach ($orders as $tmpOrder) {
                $order = $this->_orderLoader->create()->load($tmpOrder->getId());
                
                if ($order->getData('mv_inventory_id') == false) {
                    $quote = $this->_quoteLoader->create();
                    $website = $this->_websiteRepository->getById($order->getStore()->getWebsiteId());
                    $quote->setData('website', $website);
                    $quote = $quote->load($order->getQuote_id());
                    
                    if ($quote->getId()) {
                        $this->_mvOrderHelper->addOrder($order, $quote);
                    }
                }
            }
        }
    }
}
