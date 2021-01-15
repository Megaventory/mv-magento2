<?php
namespace Mv\Megaventory\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class InventoryColumn extends Column
{
    protected $_inventoriesLoader;
    protected $_scopeConfig;
    protected $_backendUrl;
    
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        array $components = [],
        array $data = []
    ) {
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_scopeConfig = $scopeConfig;
        $this->_backendUrl = $backendUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                try {
                    if ($item['mv_inventory_id'] != 0) {
                        $inventory = $this->_inventoriesLoader->create()->load($item[$this->getData('name')]);
                        $item[$this->getData('name')] = $inventory->getData('shortname');
                    } else {
                        $orderSynchronization = $this->_scopeConfig
                        ->getValue('megaventory/general/ordersynchronization');
                        
                        $notAssigned = 'Not Synchronized';
                        if (empty($orderSynchronization) || $orderSynchronization === '0') {
                            $item[$this->getData('name')] = $notAssigned;
                        } else {
                            $synchronize = '<a onclick="MegaventoryManager.synchronizeOrder(\'' . $this->_backendUrl->getUrl('megaventory/index/synchronizeOrder')  .'\','.$item['entity_id'].')" href="#">Retry</a>';
                            $item[$this->getData('name')] = $notAssigned . '<br>' .$synchronize;
                        }
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    $item[$this->getData('name')] = 'Inventory was removed';
                } catch (\Exception $exception) {
                    $item[$this->getData('name')] = '';
                }
            }
        }

        return $dataSource;
    }
}
