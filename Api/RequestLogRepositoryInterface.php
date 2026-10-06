<?php

declare(strict_types=1);

namespace Twint\Magento\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Magento\Model\RequestLog;

interface RequestLogRepositoryInterface
{
    public function getById($id);

    public function save(RequestLog $entity);

    public function getList(SearchCriteriaInterface $criteria);
}
