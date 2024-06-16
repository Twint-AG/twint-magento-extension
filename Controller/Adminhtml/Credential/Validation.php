<?php

namespace Twint\Core\Controller\Adminhtml\Credential;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Twint\Core\Validator\CredentialValidator;

class Validation extends Action implements ActionInterface, HttpPostActionInterface
{
    protected JsonFactory $jsonFactory;
    protected Http $request;
    protected CredentialValidator $validator;

    public function __construct(
        Action\Context      $context,
        JsonFactory         $jsonFactory,
        Http                $request,
        CredentialValidator $validator,
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->validator = $validator;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $cert = $this->request->get('certificate') ?? [];
        $testMode = $this->request->get('testMode') ?? false;
        $merchantId = $this->request->get('merchantId') ?? '';

        try {
            $valid = $this->validator->validate($cert, $merchantId, $testMode);
            return $resultJson->setData([
                'success' => $valid,
                'message' => $valid ? '' : __('Invalid credentials. Please check again: Merchant ID, certificate and environment (mode)')
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
