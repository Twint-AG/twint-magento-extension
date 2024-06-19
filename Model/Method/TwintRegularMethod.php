<?php

namespace Twint\Core\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\MethodInterface;
use Twint\Core\Service\PaymentService;

class TwintRegularMethod extends TwintMethod
{

    const CODE = 'twint_regular';

    protected $_code = self::CODE;

    public function __construct(Context                    $context,
                                Registry                   $registry,
                                ExtensionAttributesFactory $extensionFactory,
                                AttributeValueFactory      $customAttributeFactory,
                                Data                       $paymentData,
                                ScopeConfigInterface       $scopeConfig,
                                Logger                     $logger,
                                private PaymentService     $paymentService,
                                AbstractResource           $resource = null,
                                AbstractDb                 $resourceCollection = null,
                                array                      $data = [],
                                DirectoryHelper            $directory = null,
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);
    }

    public function canAuthorize(){
        return true;
    }

    public function getConfigPaymentAction(): string
    {
        return MethodInterface::ACTION_AUTHORIZE;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $order = $this->paymentService->createOrder($payment, $amount);
        if(!$order){
            throw new LocalizedException(__("Unable to handle payment"));
        }

        $payment->setAdditionalInformation('qrToken', (string) $order->pairingToken());

        return parent::authorize($payment, $amount);
    }
}
