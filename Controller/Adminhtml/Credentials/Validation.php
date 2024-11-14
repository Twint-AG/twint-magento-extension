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
use Twint\Magento\Validator\Input\CredentialsInputValidator;
use Twint\Sdk\Value\Environment;

class Validation extends Action implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly Http $request,
        private readonly CredentialValidator $validator,
        private readonly CredentialsInputValidator $inputValidator,
        Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Twint_Magento::payment');
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->jsonFactory->create();
        $cert = $this->request->get('certificate') ?? [];
        $environment = (string) $this->request->get('environment') ?? Environment::TESTING;
        $storeUuid = $this->request->get('storeUuid') ?? '';

        $errors = $this->inputValidator->validate($cert, $environment, $storeUuid);
        if ($errors !== []) {
            return $json->setData([
                'success' => false,
                'message' => __('Invalid input'),
                'errors' => $errors,
            ]);
        }

        try {
            $valid = $this->validator->validate($cert, $storeUuid, $environment);
            return $json->setData([
                'success' => $valid,
                'message' => $valid ? '' : __('Invalid credentials. Please check again: Store UUID, certificate and environment (mode)'),
            ]);
        } catch (Exception $e) {
            return $json->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
