<?php

declare(strict_types=1);

namespace Twint\Magento\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Magento\Model\Pairing;

interface PairingRepositoryInterface
{
    public function getById($id);

    public function save(Pairing $pairing): Pairing;

    public function lock(Pairing $pairing);

    public function unlock(Pairing $pairing);

    public function getList(SearchCriteriaInterface $criteria);

    public function getByPairingId(string $id): ?Pairing;

    public function getByOrderId(string $id): ?Pairing;

    public function getByQuoteId(string $id): ?Pairing;

    public function getUnFinishes();

    public function updateOrderId(string $orderId, int|string $quoteId): void;
}
