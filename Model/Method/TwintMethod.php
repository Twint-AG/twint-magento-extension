<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\ClientService;
use Twint\Magento\Service\RefundService;

abstract class TwintMethod extends AbstractMethod
{
    public const CODE = 'twint_payment';

    protected $_code = self::CODE;

    public function __construct(
        Context                              $context,
        Registry                             $registry,
        ExtensionAttributesFactory           $extensionFactory,
        AttributeValueFactory                $customAttributeFactory,
        Data                                 $paymentData,
        ScopeConfigInterface                 $scopeConfig,
        Logger                               $logger,
        protected ClientService              $clientService,
        protected RefundService              $refundService,
        protected PriceCurrencyInterface     $priceCurrency,
        protected PairingRepositoryInterface $pairingRepository,
        AbstractResource                     $resource = null,
        AbstractDb                           $resourceCollection = null,
        array                                $data = [],
        DirectoryHelper                      $directory = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    public function isAvailable(CartInterface $quote = null): bool
    {
        return $quote->getCurrency()
                ->getQuoteCurrencyCode() === TwintConstant::CURRENCY
            && $this->_scopeConfig->getValue(
                TwintConstant::CONFIG_VALIDATED,
                ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ) === '1'
            && $this->isEnabled($quote->getStoreId());
    }

    public function isActive($storeId = null): bool
    {
        return $this->_scopeConfig->getValue(TwintConstant::CONFIG_VALIDATED, ScopeInterface::SCOPE_STORE, $storeId)
            && $this->isEnabled($storeId);
    }

    abstract public function isEnabled(string|int $storeId);

    public function canAuthorize(): bool
    {
        return true;
    }

    public function canCapture(): bool
    {
        return true;
    }

    public function canRefund(): bool
    {
        return true;
    }

    public function canRefundPartialPerInvoice(): bool
    {
        return true;
    }

    public function canVoid(): bool
    {
        return true;
    }

    public function getConfigPaymentAction(): string
    {
        return MethodInterface::ACTION_AUTHORIZE;
    }

    public function authorize(InfoInterface $payment, $amount): self
    {
        $amount = $this->priceCurrency->convertAndRound($amount);

        /** @var Pairing $pairing */
        list($order, $pairing, $history) = $this->clientService->createOrder($payment, $amount);
        if (!$order) {
            throw new LocalizedException(__('Unable to handle payment'));
        }

        $transactionId = $pairing->getPairingId() . '-' . $history->getId();

        if ($payment instanceof Order\Payment) {
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(true);
        }
        $payment->setAdditionalInformation('pairing', $pairing->getPairingId());

        return parent::authorize($payment, $amount);
    }

    /**
     * @throws Throwable
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $amount = $this->priceCurrency->convertAndRound($amount);

        /** @var Order $order */
        $order = $payment->getOrder();

        $pairing = $this->pairingRepository->getByOrderId($order->getIncrementId());
        if (!($pairing instanceof Pairing)) {
            throw new LocalizedException(__('Cannot get Pairing record to refund'));
        }

        try {
            $refund = $this->refundService->refund($pairing, $amount);
            if($payment instanceof Order\Payment){
                $payment->setTransactionId("R-{$pairing->getPairingId()}-{$refund->getId()}");
            }
        } catch (Throwable $e) {
            $this->logger->debug([$order->getIncrementId(), $amount, $order->getStoreId()]);
            throw $e;
        }
    }
}
