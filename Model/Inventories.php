<?php
namespace Mv\Megaventory\Model;

use Magento\Framework\Exception\LocalizedException as CoreException;
use Magento\Framework\DataObject\IdentityInterface;

class Inventories extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{

    const CACHE_TAG = 'megaventory_inventories';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\ResourceModel\Inventories');
    }
    
    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
    
    public function loadBySource($sourceCode)
    {
        return $this->load($sourceCode, 'stock_source_code');
    }
    
    public function loadByName($shortName)
    {
        return $this->load($shortName, 'shortname');
    }
}
