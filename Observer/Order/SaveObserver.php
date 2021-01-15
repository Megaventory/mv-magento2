<?php
namespace Mv\Megaventory\Observer\Order;

use \Magento\Framework\Event\ObserverInterface;

class SaveObserver implements ObserverInterface
{
    private $_orderHelper;
    private $_messageManager;
    
    protected $_logger;
    
    public function __construct(
        \Mv\Megaventory\Helper\Order $orderHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_orderHelper = $orderHelper;
    
        $this->_messageManager = $messageManager;
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
    
        if ($order->getState() == 'canceled') {
            $this->_orderHelper->cancelOrder($order);
        }
    }
}
