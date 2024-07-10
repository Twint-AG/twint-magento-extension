<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Credentials;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Twint\Magento\Validator\CredentialValidator;

class Validation extends Action implements ActionInterface, HttpPostActionInterface
{
    protected JsonFactory $jsonFactory;

    protected Http $request;

    protected CredentialValidator $validator;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Http $request,
        CredentialValidator $validator,
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->validator = $validator;
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Twint_Magento::payment');
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $resultJson = $this->jsonFactory->create();
        $cert = $this->request->get('certificate') ?? [];
        $testMode = (bool) $this->request->get('testMode') ?? false;
        $merchantId = $this->request->get('merchantId') ?? '';

        try {
            $valid = $this->validator->validate($cert, $merchantId, $testMode);
            return $resultJson->setData([
                'success' => $valid,
                'message' => $valid ? '' : __('Invalid credentials. Please check again: Merchant ID, certificate and environment (mode)'),
            ]);
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
