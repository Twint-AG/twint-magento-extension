<?php
namespace Twint\Magento\Validator;

use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Encryption\Helper\Security;

class FormKeyValidator extends Validator{
    public function validate(\Magento\Framework\App\RequestInterface $request): bool
    {
        $formKey = $request->getParam('form_key', null);
        if(empty($formKey) && $request->getHeader('Content-Type', '') == 'application/json'){
            $data = json_decode($request->getContent(), true);

            $formKey = $data['form_key'] ?? '';
        }

        return $formKey && Security::compareStrings($formKey, $this->_formKey->getFormKey());
    }
}
