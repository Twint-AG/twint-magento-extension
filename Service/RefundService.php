<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Api\RefundRepositoryInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\Refund;
use Twint\Magento\Model\RefundFactory;
use Twint\Sdk\Value\Order;

class RefundService
{
    public function __construct(
        private readonly clientService              $payment,
        private readonly PairingRepositoryInterface $pairingRepository,
        private readonly RefundRepositoryInterface  $refundRepository,
        private readonly RefundFactory              $factory,
        private readonly Session                    $adminSession
    )
    {
    }

    /**
     * @param int|Pairing $pairing
     * @param float $amount
     * @return Refund
     */
    public function refund(int|Pairing $pairing, float $amount, string $reversalReference = null): Refund
    {
        if (is_int($pairing)) {
            $pairing = $this->pairingRepository->getById($pairing);
        }

        if (empty($reversalReference)) {
            $reversalReference = 'R-' . $pairing->getOrderId() . '-' . time();
        }

        $res = $this->payment->refund(
            $pairing->getPairingId(),
            $reversalReference,
            $amount,
            (int)$pairing->getStoreId()
        );

        /** @var Refund $refund */
        $refund = $this->create($res, $pairing, $reversalReference, $amount);

        return $refund;
    }

    /**
     * @throws LocalizedException
     */
    protected function validate(Pairing $pairing, float $amount): bool
    {
        if ($amount <= 0) {
            throw new LocalizedException(__('Amount to refund should be greater than 0'));
        }

        $total = $this->refundRepository->getTotalRefundedAmount($pairing->getId());

        if ($total + $amount - $pairing->getAmount() > 0) {
            throw new LocalizedException(__('Total amount should not exceed the maximum refundable amount'));
        }

        return true;
    }

    public function getRefundableAmount(int|Pairing $pairing)
    {
        if (is_int($pairing)) {
            $pairing = $this->pairingRepository->getById($pairing);
        }

        $total = $this->refundRepository->getTotalRefundedAmount($pairing->getId());

        $remaining = $pairing->getAmount() - $total;

        return max($remaining, 0);
    }

    protected function create(ApiResponse $res, Pairing $pairing, string $reversalId, float $amount)
    {
        /** @var Order $order */
        $order = $res->getReturn();

        /** @var Refund $entity */
        $entity = $this->factory->create();

        $entity->setData('pairing_id', $pairing->getId());
        $entity->setData('reversal_id', $reversalId);
        $entity->setData('amount', $amount);
        $entity->setData('currency', TwintConstant::CURRENCY);
        $entity->setData('status', (string)$order->status());
        $entity->setData('refunded_by', $this->getLoggedAdmin());
        $entity->setData('request_id', $res->getRequest()->getId());

        return $this->refundRepository->save($entity);
    }

    protected function getLoggedAdmin()
    {
        $user = $this->adminSession->getUser();

        return $user?->getUsername();
    }
}
