<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UpdateInventoryLocations extends \Magento\Backend\App\Action
{

    protected $_inventoriesHelper;
    
    public function __construct(
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_inventoriesHelper = $inventoriesHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_inventoriesHelper->updateInventoryLocations();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('megaventory/index/index');
    }
}
