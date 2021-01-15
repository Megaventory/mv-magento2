<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class SynchronizeOrder extends \Magento\Backend\App\Action
{

    protected $_orderLoader;
    protected $_quoteLoader;
    protected $_websiteLoader;
    protected $_mvOrderHelper;
    protected $_resultJsonFactory;
    
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderLoader,
        \Magento\Quote\Model\QuoteFactory $quoteLoader,
        \Magento\Store\Model\WebsiteFactory $websiteLoader,
        \Mv\Megaventory\Helper\Order $mvOrderHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_orderLoader = $orderLoader;
        $this->_quoteLoader = $quoteLoader;
        $this->_websiteLoader = $websiteLoader;
        $this->_mvOrderHelper = $mvOrderHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getPost('orderId');
                
        $order = $this->_orderLoader->create()->load($orderId);
        
        if ($order->getId()) {
            $websiteId = $order->getStore()->getWebsiteId();
            $website = $this->_websiteLoader->create()->load($websiteId);
            
            $quote = $this->_quoteLoader->create()
            ->setData('website', $website)
            ->load($order->getQuoteId());
            
            if ($quote->getId()) {
                $this->_mvOrderHelper->addOrder($order, $quote);
            }
        }
        
        return $this->_resultJsonFactory->create();
    }
}
