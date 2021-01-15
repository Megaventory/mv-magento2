<?php
namespace Mv\Megaventory\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class ProductInventoriesColumn extends Column
{
    protected $_productStocksLoader;
    protected $_inventoriesHelper;
    protected $_adminSession;
    
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Mv\Megaventory\Model\ProductstocksFactory $productStocksLoader,
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Magento\Backend\Model\Auth\Session $adminSession,
        array $components = [],
        array $data = []
    ) {
        $this->_productStocksLoader = $productStocksLoader;
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_adminSession = $adminSession;
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
            $inventories = $this->_inventoriesHelper->getInventories();
            $workOrders = $this->_adminSession->getData('mv_isWorksModuleEnabled');
            
            foreach ($dataSource['data']['items'] as & $item) {
                try {
                    if ($item['type_id'] == 'simple' || $item['type_id'] == 'virtual') {
                        if ($item['mv_product_id'] != 0) {
                            $html = '';
                            foreach ($inventories as $inventory) {
                                if ($inventory->getStockSourceCode() !== null) {
                                    $html .= '<ul style="padding-bottom:10px;">';
                                    $productStock = $this->_productStocksLoader->create()
                                    ->loadInventoryProductstock($inventory->getId(), $item['entity_id']);
                                    
                                    $html .= '<li style="width:100%;padding-right:10px;display:table-cell;"><span style="float:left;">'.$inventory->getShortname().'</span></li>';
                                    $html .= '<li title="Quantity" style="padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStockqty(), 5).'</span></li>';
                                    $html .= '<li title="Non-Shipped quantity in Sales Orders" style="color:Red;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonshippedqty(), 5).'</span></li>';
                                    if ($workOrders === true) {
                                        $html .= '<li title="Non-Allocated quantity in Work Orders" style="color:DarkRed;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonallocatedwoqty(), 5).'</span></li>';
                                    }

                                    $html .= '<li title="Non-Received quantity in Purchase Orders" style="color:Green;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonreceivedqty(), 5).'</span></li>';
                                    if ($workOrders === true) {
                                        $html .= '<li title="Non-Received quantity in Work Orders" style="color:DarkGreen;padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStocknonreceivedwoqty(), 5).'</span></li>';
                                    }

                                    $html .= '<li title="Alert quantity" style="padding-right:10px;display:table-cell;"><span style="float:left;">'.round($productStock->getStockalarmqty(), 5).'</span></li>';
                                    $html .= '</ul>';
                                }
                            }

                            $item[$this->getData('name')] = $html;
                        } else {
                            $item[$this->getData('name')] = 'Not Connected';
                        }
                    } else {
                        $item[$this->getData('name')] = '';
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    $item[$this->getData('name')] = '';
                } catch (\Exception $exception) {
                    $item[$this->getData('name')] = '';
                }
            }
        }

        return $dataSource;
    }
}
