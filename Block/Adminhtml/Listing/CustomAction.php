<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Listing;

use Magento\Ui\Component\Listing\Columns\Column;

class CustomAction extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value = ($item['shipping_id'] ?? '') . '/' . (empty($item['customer']) ? '' : "{...customer}");
                $value = $value == '/' ? '' : $value;

                $item['customer'] = $value;
            }
        }

        return $dataSource;
    }
}
