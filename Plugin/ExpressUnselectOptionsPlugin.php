<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Config\Model\Config;
use Twint\Magento\Constant\TwintConstant;

class ExpressUnselectOptionsPlugin
{
    public function beforeSave(Config $subject): array
    {
        if ($subject->getData('section') !== TwintConstant::SECTION_EXPRESS) {
            return [$subject];
        }

        $groupData = $subject->getData('groups');

        if (!isset($groupData['express']['fields']['screens'])) {
            $groupData['express']['fields']['screens'] = [
                'value' => '',
            ];
        }

        $subject->setData('groups', $groupData);

        return [$subject];
    }
}
