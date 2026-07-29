<?php

declare(strict_types=1);

namespace Twint\Magento\Provider\Data;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Twint\Magento\Api\PairingHistoryRepositoryInterface;

class RefundHistoryDataProvider extends DataProvider
{
    public function __construct(
        private readonly PairingHistoryRepositoryInterface $repository,
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = [],
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    public function addFilter(Filter $filter)
    {
        if ($filter->getField() === 'order_id') {
            $filter->setField('pairing_id');

            $ids = $this->repository->getParentIdsByOrderId($filter->getValue());
            $filter->setValue($ids);
            $filter->setConditionType('in');
        }

        parent::addFilter($filter);
    }
}
