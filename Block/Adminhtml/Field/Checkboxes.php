<?php

namespace Twint\Magento\Block\Adminhtml\Field;

use Magento\Framework\Data\Form\Element\Checkboxes as BaseCheckboxes;

class Checkboxes extends BaseCheckboxes
{
    public function getName()
    {
        $name = parent::getName();

        if (!str_ends_with($name, '[]')) {
            $name .= '[]';
        }

        return $name;
    }

    public function getChecked($value): ?string
    {
        $checked = $this->getValue() ?? $this->getData('checked');

        $array = explode(',', $checked ?? '');

        return in_array($value, $array) ? 'checked' : null;
    }
}
