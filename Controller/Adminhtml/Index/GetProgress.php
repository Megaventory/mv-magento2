<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class GetProgress extends \Magento\Backend\App\Action
{
    protected $_resourceConfig;
    protected $_resource;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $recource,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resource = $recource;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $connection = $this->_resource->getConnection();
            $tableName = $this->_resource->getTableName('megaventory_progress');
        
            $lastlastMessagesSql = 'SELECT id, messagedata FROM '.$tableName.' ORDER BY id asc';
                
            $deleteMessages = 'delete FROM '.$tableName;
            $data = [];
            $rows = $connection->fetchAll($lastlastMessagesSql);
            if (count($rows) > 0) {
                $message = '';
                $laststep = '';
                foreach ($rows as $row) {
                    $data = json_decode($row['messagedata'], true);
                    $laststep = $data['step'];
                    $message .= '<br/>'.$data['message'];
                }
        
                $data['message'] = $message;
        
                if ($laststep == 'done') {
                    $this->_resourceConfig->saveConfig('megaventory/general/syncreport', $message, 'default', 0);

                    $this->_cacheTypeList->cleanType('config');
                        
                    $connection->query($deleteMessages);
                }
            }

            return $this->_resultJsonFactory->create()->setData($data);
        } catch (\Exception $e) {
        }
    }
}
