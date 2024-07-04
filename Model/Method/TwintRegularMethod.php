<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Checkout\Model\Session;
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
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Throwable;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\ClientService;

class TwintRegularMethod extends TwintMethod
{
    public const CODE = 'twint_regular';

    public $logger;

    protected $_code = self::CODE;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        private ClientService $clientService,
        private Session $checkoutSession,
        private PriceCurrencyInterface $priceCurrency,
        private PairingRepositoryInterface $pairingRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
    }

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

        $this->checkoutSession->setPairingId($pairing->getPairingId() . '-' . $history->getId());

        return parent::authorize($payment, $amount);
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $amount = $this->priceCurrency->convertAndRound($amount);

        /** @var Order $order */
        $order = $payment->getOrder();

        $pairing = $this->pairingRepository->getByOrderId($order->getIncrementId());
        if (!($pairing instanceof Pairing)) {
            throw new LocalizedException(__('Cannot get Pairing record to refund'));
        }

        $reversalId = __('R-%1-%2', $order->getIncrementId(), time());

        try {
            $this->clientService->refund($pairing->getPairingId(), $reversalId, $amount, $order->getStoreId());
        } catch (Throwable $e) {
            $this->logger->debug([$order->getIncrementId(), $reversalId, $amount, $order->getStoreId()]);
            dd($e->getMessage());

            throw $e;
        }
    }
}
