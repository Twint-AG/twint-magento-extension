<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Twint\Magento\Constant\TwintConstant;

class ExpressUnselectOptionsPlugin
{
    public function afterGetConfigData(Save $subject, array $result): array
    {
        if ($result['section'] === TwintConstant::SECTION_EXPRESS && !isset($result['groups']['express']['fields']['screens'])) {
            $result['groups']['express']['fields']['screens'] = [
                'value' => '',
            ];
        }

        return $result;
    }
}
