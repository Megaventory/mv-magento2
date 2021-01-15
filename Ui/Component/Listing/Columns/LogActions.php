<?php
namespace Mv\Megaventory\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class LogActions extends Column
{
    /** Url path */
    const LOG_URL_PATH_REDO = 'megaventory/log/redo';
    const LOG_URL_PATH_DELETE = 'megaventory/log/delete';

    /** @var UrlInterface */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
                $name = $this->getData('name');
                if (isset($item['log_id'])) {
                    $item[$name]['redo'] = [
                        'href' => $this->urlBuilder->getUrl(self::LOG_URL_PATH_REDO, ['log_id' => $item['log_id']]),
                        'label' => __('Redo')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::LOG_URL_PATH_DELETE, ['log_id' => $item['log_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete'),
                            'message' => __('Are you sure you wan\'t to delete this record?')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
