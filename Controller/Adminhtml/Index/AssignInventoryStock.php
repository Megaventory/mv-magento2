<?php

namespace Mv\Megaventory\Controller\Adminhtml\Index;

class AssignInventoryStock extends \Magento\Backend\App\Action
{
    private $_mvInventory;
    private $_sourceRepository;
    private $_sessionMessageManager;
    private $_inventoryHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepo,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Mv\Megaventory\Model\InventoriesFactory $mvInventoryFactory,
        \Mv\Megaventory\Helper\Inventories $mvInventoryHelper
    ) {
        $this->_mvInventory = $mvInventoryFactory;
        $this->_sourceRepository = $sourceRepo;
        $this->_sessionMessageManager = $messageManager;
        $this->_inventoryHelper = $mvInventoryHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $inventoryId = (int)$this->getRequest()->getParam('inventory_id');
        $mvInventory = $this->_mvInventory->create()->load($inventoryId);
        if (($mvInventory->getId() === null) || ($mvInventory->getId() < 1)) {
            $this->_sessionMessageManager->addErrorMessage('Megaventory location not found');
            return $this->_redirect($this->_redirect->getRefererUrl());
        }
        try {
            $this->_sourceRepository->get($this->getRequest()->getParam('source_code'));
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            $errorMessage = 'Inventory source with source code "';
            $errorMessage .= $this->getRequest()->getParam('source_code');
            $errorMessage .= '" does not exist';
            $this->_sessionMessageManager->addErrorMessage($errorMessage);
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        try {
            $result = $this->_inventoryHelper->assignLocationToSource(
                $mvInventory,
                $this->_sourceRepository->get($this->getRequest()->getParam('source_code'))
            );
        } catch (\Exception $e) {
            $this->_sessionMessageManager->addErrorMessage('A general error has occurred.');
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        if ($result['status'] == 'error') {
            $this->_sessionMessageManager->addErrorMessage($result['message']);
        } elseif ($result['status'] == 'success') {
            $this->_sessionMessageManager->addSuccessMessage($result['message']);
        }
        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
