<?php
namespace Mv\Megaventory\Model\ResourceModel\Updates;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Api\Search\SearchResultInterface;
use \Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection implements SearchResultInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_mvHelper;
    protected $_scopeConfig;
    
    /**
     * Define resource model
     *
     * @return void
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        ScopeConfigInterface $scopeConfig,
        \Mv\Megaventory\Helper\Data $mvHelper
    ) {
        parent::__construct($entityFactory);
        parent::setItemObjectClass(\Magento\Framework\View\Element\UiComponent\DataProvider\Document::class);
        $this->_mvHelper = $mvHelper;
        $this->_scopeConfig = $scopeConfig;
    }
    
    public function loadData($printQuery = false, $logQuery = false)
    {
        $key = $this->_scopeConfig->getValue('megaventory/general/apikey');
            
        $magentoId = $this->_scopeConfig->getValue('megaventory/general/magentoid');
        
        if (!isset($magentoId)) {
            $magentoId = "magento";
        }
            
        $data =
        [
                'APIKEY' => $key,
                'Filters' => [
                                "AndOr" => "And",
                                "FieldName" => "Application",
                                "SearchOperator" => "Equals",
                                "SearchValue" => $magentoId
                             ]
        ];
         
        $json_result = $this->_mvHelper->makeJsonRequest($data, "IntegrationUpdateGet");
        
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode == '0') {
            $mvIntegrationUpdates = $json_result['mvIntegrationUpdates'];
                
            foreach ($mvIntegrationUpdates as $mvIntegrationUpdate) {
                $item = $this->getNewEmptyItem();
                $arraykeys = array_keys($mvIntegrationUpdate);
                foreach ($arraykeys as $arraykey) {
                    if ($arraykey == 'IntegrationUpdateDateTime') {
                        $updateDT = $mvIntegrationUpdate[$arraykey];
                        $updateDT = substr($updateDT, 6, 13);
                        $seconds = $updateDT / 1000;
                        date("d-m-Y", $seconds);
                        $mvIntegrationUpdate[$arraykey] = date("m-d-Y H:i:s T", $seconds);
                    }

                    $item->setData($arraykey, $mvIntegrationUpdate[$arraykey]);
                }

                parent::_addItem($item);
            }
        }
        
        return $this;
    }
    
    public function getItems()
    {
        return parent::getItems();
    }
    
    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\Search\DocumentInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        parent::setItems($items);
    }
    
    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    
    public function getAggregations()
    {
        return parent::getAggregations();
    }
    
    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        parent::setAggregations($aggregations);
    }
    
    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        parent::getSearchCriteria();
    }
    
    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        parent::setSearchCriteria($searchCriteria);
    }
    
    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return 0;
    }
    
    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        parent::setTotalCount($totalCount);
    }
}
