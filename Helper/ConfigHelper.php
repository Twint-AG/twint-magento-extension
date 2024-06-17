<?php
namespace Twint\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Twint\Core\Constant\TwintConstant;

class ConfigHelper extends AbstractHelper{
    public function getCredentials(string $scope){
        return match ($scope) {
            'websites' => $this->scopeConfig->getValue(TwintConstant::CONFIG_CREDENTIALS, ScopeInterface::SCOPE_WEBSITES),
            'stores' => $this->scopeConfig->getValue('twint/credentials', ScopeInterface::SCOPE_STORES),
            default => $this->scopeConfig->getValue('twint/credentials', 'default'),
        };
    }
}
