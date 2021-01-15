<?php

namespace Mv\Megaventory\Model\Config\Source;

class SourceSelectionAlgorithmConfigSource implements \Magento\Framework\Data\OptionSourceInterface
{
    protected $_sourceSelectionAlgorithmListInterface;
    protected $_scopeConfig;

    public function __construct(
        \Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface $sourceSelectionInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_sourceSelectionAlgorithmListInterface = $sourceSelectionInterface;
        $this->_scopeConfig = $scopeConfig;
    }

    public function toOptionArray()
    {
        $currentCode = $this->_scopeConfig->getValue('megaventory/orders/source_selection_algorithm_code');
        $options = [];

        $algorithmList = $this->_sourceSelectionAlgorithmListInterface->execute();

        foreach ($algorithmList as $algorithm) {
            $isSelected = ($algorithm->getCode() == $currentCode);
            $options[] = [
                'value'=>$algorithm->getCode(),
                'label'=>$algorithm->getTitle(),
                'selected'=>$isSelected
            ];
        }

        return $options;
    }
}
