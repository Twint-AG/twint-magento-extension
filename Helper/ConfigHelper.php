<?php

declare(strict_types=1);

namespace Twint\Magento\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Config\Generic;

class ConfigHelper extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $state
    ) {
        parent::__construct($context);
    }

    public function getCredentials(string $scope)
    {
        return match ($scope) {
            'websites' => $this->scopeConfig->getValue(
                TwintConstant::CONFIG_CREDENTIALS,
                ScopeInterface::SCOPE_WEBSITES
            ),
            'stores' => $this->scopeConfig->getValue(TwintConstant::CONFIG_CREDENTIALS, ScopeInterface::SCOPE_STORES),
            default => $this->scopeConfig->getValue(TwintConstant::CONFIG_CREDENTIALS, 'default'),
        };
    }

    public function getConfigs($sStoreCode = null)
    {
        $sScopeCode = ScopeInterface::SCOPE_STORES;
        if (!$sStoreCode) {
            list($sStoreCode, $sScopeCode) = $this->fetchCurrentStoreCode();
        }

        return new Generic($this->scopeConfig->getValue(TwintConstant::SECTION, $sScopeCode, $sStoreCode));
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function fetchCurrentStoreCode(): array
    {
        $sScopeCode = ScopeInterface::SCOPE_STORES;
        $sStoreCode = $this->storeManager->getStore()
            ->getCode();
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            $sStoreCode = 0; // 0 = default config, which should be used when neither website nor store parameter are present, storeManager returns default STORE though, which would be wrong
            if (!empty($this->getRequestParameter('website'))) {
                $sStoreCode = $this->getRequestParameter('website');
                $sScopeCode = ScopeInterface::SCOPE_WEBSITES;
            }
            if (!empty($this->getRequestParameter('store'))) {
                $sStoreCode = $this->getRequestParameter('store');
            }
        }

        return [$sStoreCode, $sScopeCode];
    }

    public function getRequestParameter($sParameter)
    {
        return $this->_getRequest()
            ->getParam($sParameter);
    }
}
