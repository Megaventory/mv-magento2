<?php
namespace Mv\Megaventory\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mv\Megaventory\Model\ResourceModel\Log\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 */
class Redo extends \Magento\Backend\App\Action
{
    protected $_mvLogFactory;
    public function __construct(
        \Mv\Megaventory\Model\LogFactory $logFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_mvLogFactory = $logFactory;
        parent::__construct($context);
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return true;
        //return $this->_authorization->isAllowed('Ashsmith_Blog::save');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('log_id');
        $model = $this->_mvLogFactory->create();

        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($id) {
            try {
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This log no longer exists.'));
                    /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (\Exception $e) {
                    $this->messageManager->addError(__('We can\'t find the log to redo action.'));
                    return $resultRedirect->setPath('*/*/', ['log_id' => $id]);
            }
        }
        
        return $resultRedirect->setPath('*/*/');
    }
}
