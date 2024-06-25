<?php
namespace Twint\Magento\Api;


use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Magento\Model\Pairing;

interface PairingRepositoryInterface{
    public function getById($id);

    public function save(Pairing $pairing);
    public function lock(Pairing $pairing);
    public function unlock(Pairing $pairing);

    public function getList(SearchCriteriaInterface $criteria);

    public function getByPairingId(string $id): ?Pairing;
}
