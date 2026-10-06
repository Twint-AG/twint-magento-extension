<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Source;

use Magento\Config\Model\Config\Source\Yesno;

class Base extends Yesno
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $assocArray = [];
        foreach ($this->toOptionArray() as $item) {
            $assocArray[$item['key']] = $item['value'];
        }

        return $assocArray;
    }
}
