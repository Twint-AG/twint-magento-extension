<?php

declare(strict_types=1);

namespace Twint\Magento\Provider\Data;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Sales\Api\OrderRepositoryInterface;

class PairingGridDataProvider extends DataProvider
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
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
        // Set the default filter value based on the current order increment_id
        if ($filter->getField() === 'order_id' && $filter->getValue()) {
            // Retrieve the current order increment_id from the registry or request
            $filter->setValue($this->getOrderIncrementId($filter->getValue()));
        }

        parent::addFilter($filter);
    }

    protected function getOrderIncrementId($orderId): string
    {
        return $this->repository->get($orderId)
            ->getIncrementId();
    }
}
