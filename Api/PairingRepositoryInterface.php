<?php
namespace Twint\Core\Api;


use Magento\Framework\Api\SearchCriteriaInterface;
use Twint\Core\Model\Pairing;

interface PairingRepositoryInterface{
    public function getById($id);

    public function save(Pairing $pairing);

    public function getList(SearchCriteriaInterface $criteria);
}
