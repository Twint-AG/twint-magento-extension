<?php

declare(strict_types=1);

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

        return in_array($value, $array, true) ? 'checked' : null;
    }

    public function getDisabled($value)
    {
        $disabled = $this->getData('disabled');

        return $disabled ? 'disabled' : '';
    }
}
