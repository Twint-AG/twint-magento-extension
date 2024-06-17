<?php
namespace Twint\Core\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;

abstract class TwintMethod extends AbstractMethod{
    const CODE = 'twint_payment';

    protected $_code = self::CODE;

    public function __construct(\Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = [], DirectoryHelper $directory = null)
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data, $directory);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    public function isActive($storeId = null)
    {
        return true;
    }
}
