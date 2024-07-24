<?php

declare(strict_types=1);

namespace Twint\Magento\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Magento\Model\PairingHistory;

interface PairingHistoryRepositoryInterface
{
    public function getById($id);

    public function save(PairingHistory $pairing);

    public function getList(SearchCriteriaInterface $criteria);

    public function updateOrderId(string $orderId, int|string $quoteId): void;
}
