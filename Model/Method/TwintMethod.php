<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Method;

use Magento\Checkout\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Service\ClientService;

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
        protected Session                    $checkoutSession,
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
        return $quote->getCurrency()->getStoreCurrencyCode() === TwintConstant::CURRENCY
            && $this->_scopeConfig->getValue(
                TwintConstant::CONFIG_VALIDATED,
                ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ) == 1
            && $this->isEnabled($quote->getStoreId());
    }

    public function isActive($storeId = null): bool
    {
        return $this->_scopeConfig->getValue(
                TwintConstant::CONFIG_VALIDATED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) == 1
            && $this->isEnabled($storeId);
    }

    abstract function isEnabled(string|int $storeId);
}
