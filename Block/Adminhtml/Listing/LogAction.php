<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Listing;

use Magento\Sales\Ui\Component\Listing\Column\ViewAction as MagentoViewAction;

class LogAction extends MagentoViewAction
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $viewUrlPath = 'twint/request/view';
            $label = __('Details');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = [
                    'view' => [
                        'href' => $this->urlBuilder->getUrl($viewUrlPath, [
                            'id' => $item['request_id'],
                        ]),
                        'label' => $label,
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
