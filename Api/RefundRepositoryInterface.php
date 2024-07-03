<?php

declare(strict_types=1);

namespace Twint\Magento\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Magento\Model\Refund;

interface RefundRepositoryInterface
{
    public function getById($id);

    public function save(Refund $entity);

    public function getList(SearchCriteriaInterface $criteria);

    public function getTotalRefundedAmount(int $pairingId): float;
}
