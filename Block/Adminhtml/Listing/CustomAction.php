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
                $value = empty($item['customer']) ? '' : substr($item['customer'], 0, 50) . '...';

                $item['customer'] = $value;
            }
        }

        return $dataSource;
    }
}
